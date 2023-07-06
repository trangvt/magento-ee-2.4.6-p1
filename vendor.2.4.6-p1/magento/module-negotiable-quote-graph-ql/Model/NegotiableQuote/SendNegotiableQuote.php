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
 * Model for sending a negotiable quote for admin review with an optional comment
 */
class SendNegotiableQuote
{
    /**
     * @var NegotiableQuoteManagementInterface
     */
    private $negotiableQuoteManagement;

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
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource
    ) {
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
    }

    /**
     * Submit a negotiable quote for review by the seller
     *
     * @param string $maskedId
     * @param string $comment
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
        string $comment,
        int $customerId,
        WebsiteInterface $website
    ): NegotiableQuoteInterface {
        $this->customer->validateCanManage($customerId);

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedId);
        $quote = $this->quote->getOwnedQuote($quoteId, $website);
        $this->quote->validateNegotiable([$quote]);
        $this->quote->validateCanSubmit([$quote]);

        if (!$this->negotiableQuoteManagement->send($quoteId, $comment)) {
            throw new LocalizedException(__("Failed to submit the negotiable quote for review."));
        }

        $quote = $this->negotiableQuoteManagement->getNegotiableQuote($quoteId);
        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }
}
