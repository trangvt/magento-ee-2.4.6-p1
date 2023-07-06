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
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\SetBillingAddressOnCart;
use Magento\Tax\Helper\Data as TaxHelper;

/**
 * Model for setting a billing address on a negotiable quote
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetNegotiableQuoteBillingAddress
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
      * @var SetBillingAddressOnCart
      */
     private $setBillingAddressOnCart;

    /**
     * @var NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @param Customer $customer
     * @param Quote $quote
     * @param QuoteIdMask $quoteIdMaskResource
     * @param SetBillingAddressOnCart $setBillingAddressOnCart
     * @param NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param TaxHelper $taxHelper
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        Customer $customer,
        Quote $quote,
        QuoteIdMask $quoteIdMaskResource,
        SetBillingAddressOnCart $setBillingAddressOnCart,
        NegotiableQuoteItemManagementInterface $quoteItemManagement,
        TaxHelper $taxHelper,
        IdEncoder $idEncoder
    ) {
        $this->customer = $customer;
        $this->quote = $quote;
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->setBillingAddressOnCart = $setBillingAddressOnCart;
        $this->taxHelper = $taxHelper;
        $this->quoteItemManagement = $quoteItemManagement;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Set billing address on a negotiable quote
     *
     * @param ContextInterface $context
     * @param string $maskedId
     * @param array $billingAddressInput
     *
     * @return NegotiableQuoteInterface
     * @throws LocalizedException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(
        $context,
        string $maskedId,
        array $billingAddressInput
    ): NegotiableQuoteInterface {
        $this->customer->validateCanProceedToCheckout((int)$context->getUserId());

        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedId);
        $quote = $this->quote->getOwnedQuote($quoteId, $context->getExtensionAttributes()->getStore()->getWebsite());
        $this->quote->validateNegotiable([$quote]);
        $this->quote->validateCanProceedToCheckout($quote);
        $this->checkForInputExceptions($billingAddressInput);

        if (isset($billingAddressInput['customer_address_uid'])) {
            $addressId = $this->idEncoder->decode($billingAddressInput['customer_address_uid']);
            if ($addressId !== false) {
                $billingAddressInput['customer_address_id'] = $addressId;
            } else {
                throw new GraphQlNoSuchEntityException(
                    __(
                        'Invalid address ID "%address_id"',
                        ['address_id' => $billingAddressInput['customer_address_uid']]
                    )
                );
            }
        }

        $this->setBillingAddressOnCart->execute($context, $quote, $billingAddressInput);
        $this->recalculateByAddressChange($quote);

        return $quote->getExtensionAttributes()->getNegotiableQuote();
    }

    /**
     * Check for the input exceptions
     *
     * @param array|null $billingAddressInput
     * @throws GraphQlInputException
     */
    private function checkForInputExceptions(
        ?array $billingAddressInput
    ) {
        $customerAddressId = $billingAddressInput['customer_address_uid'] ?? null;
        $addressInput = $billingAddressInput['address'] ?? null;
        $sameAsShipping = $billingAddressInput['same_as_shipping'] ?? null;

        if (null === $customerAddressId && null === $addressInput && empty($sameAsShipping)) {
            throw new GraphQlInputException(
                __('The billing address must contain either "customer_address_uid", "address", or "same_as_shipping".')
            );
        }

        if ($customerAddressId && $addressInput) {
            throw new GraphQlInputException(
                __('The billing address cannot contain "customer_address_uid" and "address" at the same time.')
            );
        }
    }

    /**
     * Recalculate price changes on the quote based on an address change.
     *
     * @param CartInterface $quote
     * @throws NoSuchEntityException
     */
    private function recalculateByAddressChange(CartInterface $quote): void
    {
        $quoteExtensionAttributes = $quote->getExtensionAttributes();
        if ($quoteExtensionAttributes
            && $quoteExtensionAttributes->getNegotiableQuote()
            && $quoteExtensionAttributes->getNegotiableQuote()->getIsRegularQuote()
        ) {
            $negotiableQuote = $quoteExtensionAttributes->getNegotiableQuote();
            $negotiableQuote->setIsAddressDraft(true);
            if ($this->taxHelper->getTaxBasedOn() == 'billing'
                || $this->taxHelper->getTaxBasedOn() == 'shipping'
                && $quote->getIsVirtual()
            ) {
                $isNeedRecalculate = $negotiableQuote->getNegotiatedPriceValue() === null;
                $this->quoteItemManagement
                    ->recalculateOriginalPriceTax($quote->getId(), $isNeedRecalculate, $isNeedRecalculate);
            }
        }
    }
}
