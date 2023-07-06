<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote;

use Magento\Checkout\Api\Exception\PaymentProcessingRateLimitExceededException;
use Magento\Checkout\Api\PaymentSavingRateLimiterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\Payment\PaymentMethodBuilder;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Saves related payment method info for a negotiable quote.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetNegotiableQuotePaymentMethod
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var QuoteIdMask
     */
    private $quoteIdMask;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var PaymentMethodBuilder
     */
    private $paymentMethodBuilder;

    /**
     * @var PaymentSavingRateLimiterInterface
     */
    private $paymentRateLimiter;

    /**
     * @param Quote $quote
     * @param Customer $customer
     * @param QuoteIdMask $quoteIdMask
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentMethodBuilder $paymentMethodBuilder
     * @param PaymentSavingRateLimiterInterface $savingRateLimiter
     */
    public function __construct(
        Quote $quote,
        Customer $customer,
        QuoteIdMask $quoteIdMask,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentMethodBuilder $paymentMethodBuilder,
        ?PaymentSavingRateLimiterInterface $savingRateLimiter
    ) {
        $this->quote = $quote;
        $this->customer = $customer;
        $this->quoteIdMask = $quoteIdMask;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentMethodBuilder = $paymentMethodBuilder;
        $this->paymentRateLimiter = $savingRateLimiter;
    }

    /**
     * Set payment method on the negotiable quote
     *
     * @param string $maskedQuoteId
     * @param array $paymentData
     * @param int $customerId
     * @param WebsiteInterface $website
     * @return NegotiableQuoteInterface
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(
        string $maskedQuoteId,
        array $paymentData,
        int $customerId,
        WebsiteInterface $website
    ): NegotiableQuoteInterface {
        $this->customer->validateNegotiableQuotesEnabled($customerId);
        try {
            $this->customer->validateCanProceedToCheckout($customerId);
        } catch (GraphQlAuthorizationException $ex) {
            throw new GraphQlAuthorizationException(
                __("The current customer does not have permission to set payment method on the negotiable quote.")
            );
        }
        $unmaskedQuoteId = $this->quoteIdMask->getUnmaskedQuoteId($maskedQuoteId);
        $quote = $this->quote->getOwnedQuote($unmaskedQuoteId, $website);
        $this->quote->validateNegotiable([$quote]);
        try {
            $this->quote->validateCanProceedToCheckout($quote);
        } catch (GraphQlInputException $ex) {
            throw new GraphQlInputException(
                __(
                    "The quote %quoteId is currently locked, and you cannot set the payment method at the moment.",
                    ['quoteId' => $maskedQuoteId]
                )
            );
        }

        try {
            $this->paymentRateLimiter->limit();
        } catch (PaymentProcessingRateLimitExceededException $ex) {
            //Limit reached
            throw new GraphQlInputException(__($ex->getMessage()), $ex);
        }

        $payment = $this->paymentMethodBuilder->build($paymentData);

        try {
            $this->paymentMethodManagement->set($unmaskedQuoteId, $payment);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }
}
