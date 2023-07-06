<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for updating negotiable quote item quantities
 */
class UpdateNegotiableQuoteItemsForUser
{
    /**
     * @var NegotiableQuoteManagementInterface
     */
    private $negotiableQuoteManagement;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

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
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param IdEncoder $idEncoder
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        IdEncoder $idEncoder,
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource
    ) {
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->idEncoder = $idEncoder;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * Update negotiable quote item quantities
     *
     * @param string $maskedId
     * @param array $items
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return NegotiableQuoteInterface
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(
        string $maskedId,
        array $items,
        int $customerId,
        WebsiteInterface $website
    ): NegotiableQuoteInterface {
        $this->customer->validateCanManage($customerId);

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedId);
        $quote = $this->quote->getOwnedQuote($quoteId, $website);
        $this->quote->validateNegotiable([$quote]);
        $this->quote->validateCanSubmit([$quote]);

        $cartData = [];
        $itemIds = [];
        foreach ($items as $item) {
            $quoteItemId = $this->idEncoder->decode((string)$item['quote_item_uid']);
            $itemIds[] = $quoteItemId;
            $cartData[$quoteItemId] = ['qty' => $item['quantity']];
        }
        $this->quote->validateHasItems($quote, $itemIds);

        try {
            $this->negotiableQuoteManagement->updateQuoteItems($quoteId, $cartData);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
        $this->negotiableQuoteManagement->updateProcessingByCustomerQuoteStatus($quoteId);

        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }
}
