<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Negotiable Quote model with related validation methods
 */
class NegotiableQuote
{
    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMaskResource;

    /**
     * @var bool
     */
    private $isNegotiableOperation;

    /**
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        QuoteIdMask $quoteIdMaskResource
    ) {
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->isNegotiableOperation = false;
    }

    /**
     * Retrieve negotiable quotes and validate quotes for all ids exist and belong to the current customer and website
     *
     * @param string[] $maskedQuoteIds
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return CartInterface[]
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function getOwnedNegotiableQuotes(
        array $maskedQuoteIds,
        int $customerId,
        WebsiteInterface $website
    ): array {
        $quoteIds = $this->quoteIdMaskResource->getUnmaskedQuoteIds($maskedQuoteIds, true);
        $validStoreIds = $website->getStoreIds();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('extension_attribute_negotiable_quote.quote_id', $quoteIds, 'in')
            ->addFilter('customer_id', $customerId)
            ->addFilter('store_id', $validStoreIds, 'in')
            ->create();

        /**
         * @var CartInterface[] $quotes
         */
        $quotes = $this->negotiableQuoteRepository->getList($searchCriteria)->getItems();
        $ownedQuotes = [];

        foreach ($quotes as $quote) {
            $maskedQuoteId = $this->quoteIdMaskResource->getMaskedQuoteId((int)$quote->getId());
            $ownedQuotes[$maskedQuoteId] = $quote;
        }

        return $ownedQuotes;
    }

    /**
     * Set a flag for whether or not a negotiable quote-specific operation is underway
     *
     * @param bool $isNegotiableOperation
     */
    public function setIsNegotiableQuoteOperation(bool $isNegotiableOperation): void
    {
        $this->isNegotiableOperation = $isNegotiableOperation;
    }

    /**
     * Retrieve the flag for whether or not a negotiable quote-specific operation is underway
     *
     * @return bool
     */
    public function isNegotiableQuoteOperation(): bool
    {
        return $this->isNegotiableOperation;
    }
}
