<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for creating a negotiable quote
 */
class RequestNegotiableQuoteForUser
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CommentManagementInterface
     */
    private $commentManagement;

    /**
     * @var History
     */
    private $quoteHistory;

    /**
     * @var NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * @var NegotiableQuoteConverter
     */
    private $negotiableQuoteConverter;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMaskResource;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CommentManagementInterface $commentManagement
     * @param History $quoteHistory
     * @param NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param NegotiableQuoteConverter $negotiableQuoteConverter
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CommentManagementInterface $commentManagement,
        History $quoteHistory,
        NegotiableQuoteItemManagementInterface $quoteItemManagement,
        NegotiableQuoteConverter $negotiableQuoteConverter,
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->commentManagement = $commentManagement;
        $this->quoteHistory = $quoteHistory;
        $this->quoteItemManagement = $quoteItemManagement;
        $this->negotiableQuoteConverter = $negotiableQuoteConverter;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * Create NegotiableQuote
     *
     * @param string $maskedId
     * @param string $quoteName
     * @param string $comments
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return NegotiableQuoteInterface
     * @throws GraphQlAlreadyExistsException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(
        string $maskedId,
        string $quoteName,
        string $comments,
        int $customerId,
        WebsiteInterface $website
    ): NegotiableQuoteInterface {
        $this->customer->validateCanManage($customerId);

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedId);
        $quote = $this->quote->getOwnedQuote($quoteId, $website);
        if (!$quote->getIsActive()) {
            throw new GraphQlInputException(__("Cannot create a negotiable quote for an inactive cart."));
        }

        if ($quote->getExtensionAttributes()
            && $quote->getExtensionAttributes()->getNegotiableQuote()
            && $quote->getExtensionAttributes()->getNegotiableQuote()->getQuoteId() !== null) {
            throw new GraphQlAlreadyExistsException(__("Negotiable quote already exists for the specified UID."));
        }

        if (!$quote->getItemsCount()) {
            throw new GraphQlInputException(__("Cannot create a negotiable quote for an empty cart."));
        }

        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();

        $negotiableQuote->setQuoteName($quoteName);
        $negotiableQuote->setQuoteId($quoteId);
        $negotiableQuote->setIsRegularQuote(true);
        $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_CREATED);
        $negotiableQuote->setCreatorId($customerId);

        try {
            $this->quoteRepository->save($quote);
            $this->quoteItemManagement->updateQuoteItemsCustomPrices($quoteId);
            $this->commentManagement->update($quoteId, $comments);
            $this->quoteHistory->createLog($quoteId);
            $this->updateSnapshotQuote($quoteId, $website);
        } catch (CouldNotSaveException $exception) {
            throw new LocalizedException(__("An error occurred while attempting to create the negotiable quote."));
        }

        return $negotiableQuote;
    }

    /**
     * Updates data of snapshot quote.
     *
     * @param int $quoteId
     * @param WebsiteInterface $website
     * @throws GraphQlNoSuchEntityException
     */
    private function updateSnapshotQuote(int $quoteId, WebsiteInterface $website): void
    {
        $quote = $this->quote->getOwnedQuote($quoteId, $website);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $negotiableQuote->setSnapshot(json_encode($this->negotiableQuoteConverter->quoteToArray($quote)));
        $this->quoteRepository->save($quote);
    }
}
