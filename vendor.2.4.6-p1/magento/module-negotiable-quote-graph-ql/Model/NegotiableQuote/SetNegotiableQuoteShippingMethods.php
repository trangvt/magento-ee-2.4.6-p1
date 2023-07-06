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
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\QuoteGraphQl\Model\Cart\SetShippingMethodsOnCartInterface;

/**
 * Saves related shipping method info for a negotiable quote.
 */
class SetNegotiableQuoteShippingMethods
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
     * @var SetShippingMethodsOnCartInterface
     */
    private $setShippingMethodsOnCart;

    /**
     * @param Quote $quote
     * @param Customer $customer
     * @param QuoteIdMask $quoteIdMask
     * @param SetShippingMethodsOnCartInterface $setShippingMethodsOnCart
     */
    public function __construct(
        Quote $quote,
        Customer $customer,
        QuoteIdMask $quoteIdMask,
        SetShippingMethodsOnCartInterface $setShippingMethodsOnCart
    ) {
        $this->quote = $quote;
        $this->customer = $customer;
        $this->quoteIdMask = $quoteIdMask;
        $this->setShippingMethodsOnCart = $setShippingMethodsOnCart;
    }

    /**
     * Set shipping method on the negotiable quote
     *
     * @param ContextInterface $context
     * @param string $maskedQuoteId
     * @param array $shippingMethods
     * @return NegotiableQuoteInterface
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(
        $context,
        string $maskedQuoteId,
        array $shippingMethods
    ): NegotiableQuoteInterface {
        $this->customer->validateNegotiableQuotesEnabled((int)$context->getUserId());
        try {
            $this->customer->validateCanProceedToCheckout((int)$context->getUserId());
        } catch (GraphQlAuthorizationException $ex) {
            throw new GraphQlAuthorizationException(
                __("The current customer does not have permission to set shipping method on the negotiable quote.")
            );
        }
        $unmaskedQuoteId = $this->quoteIdMask->getUnmaskedQuoteId($maskedQuoteId);
        $quote = $this->quote->getOwnedQuote(
            $unmaskedQuoteId,
            $context->getExtensionAttributes()->getStore()->getWebsite()
        );
        $this->quote->validateNegotiable([$quote]);
        try {
            $this->quote->validateCanProceedToCheckout($quote);
        } catch (GraphQlInputException $ex) {
            throw new GraphQlInputException(
                __(
                    "The quote %quoteId is currently locked, and you cannot set the shipping method at the moment.",
                    ['quoteId' => $maskedQuoteId]
                )
            );
        }

        try {
            $this->setShippingMethodsOnCart->execute($context, $quote, $shippingMethods);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }
}
