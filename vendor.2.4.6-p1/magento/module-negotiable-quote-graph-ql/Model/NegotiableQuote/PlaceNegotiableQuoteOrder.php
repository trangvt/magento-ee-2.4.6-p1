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
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteManagement;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;

/**
 * Model to validate and place an order on a Negotiable Quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceNegotiableQuoteOrder
{

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
     * @var NegotiableQuoteManagement
     */
    private $negotiableQuoteManagement;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentManagement;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     * @param NegotiableQuoteManagement $negotiableQuoteManagement
     * @param PaymentMethodManagementInterface $paymentManagement
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource,
        NegotiableQuoteManagement $negotiableQuoteManagement,
        PaymentMethodManagementInterface $paymentManagement,
        CartManagementInterface $cartManagement
    ) {
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->paymentManagement = $paymentManagement;
        $this->cartManagement = $cartManagement;
    }

    /**
     * Place an order on a negotiable quote
     *
     * @param ContextInterface $context
     * @param string $maskedCartId
     * @return int
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(ContextInterface $context, string $maskedCartId): int
    {
        $userId = (int)$context->getUserId();
        $this->customer->validateCanProceedToCheckout($userId);
        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedCartId);
        $quote = $this->quote->getOwnedQuote($quoteId, $context->getExtensionAttributes()->getStore()->getWebsite());
        $this->quote->validateNegotiable([$quote]);
        $this->quote->validateCanProceedToCheckout($quote);

        $paymentMethod = $this->paymentManagement->get($quoteId);
        $orderId = $this->cartManagement->placeOrder($quoteId, $paymentMethod);
        $this->negotiableQuoteManagement->order($quote->getId());

        return (int)$orderId;
    }
}
