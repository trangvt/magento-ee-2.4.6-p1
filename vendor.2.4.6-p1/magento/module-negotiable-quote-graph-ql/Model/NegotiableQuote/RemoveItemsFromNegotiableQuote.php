<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Model for removing items from a negotiable quote
 */
class RemoveItemsFromNegotiableQuote
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
     * Remove items from a negotiable quote
     *
     * @param string $maskedId
     * @param array $itemIds
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return NegotiableQuoteInterface
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(
        string $maskedId,
        array $itemIds,
        int $customerId,
        WebsiteInterface $website
    ): NegotiableQuoteInterface {
        $this->customer->validateCanManage($customerId);

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedId);
        $quote = $this->quote->getOwnedQuote($quoteId, $website);
        $this->quote->validateNegotiable([$quote]);
        $this->quote->validateCanSubmit([$quote]);
        $this->quote->validateHasItems($quote, $itemIds);

        $failedIds = [];
        foreach ($itemIds as $itemId) {
            try {
                if (!$this->negotiableQuoteManagement->removeQuoteItem($quoteId, (int)$itemId)) {
                    $failedIds[] = $itemId;
                }
            } catch (\Exception $e) {
                $failedIds[] = $itemId;
            }
        }

        if ($failedIds) {
            throw new LocalizedException(
                __(
                    "Could not remove the items with the following IDs: "
                    . implode(", ", $this->idEncoder->encodeList($failedIds))
                )
            );
        }

        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }
}
