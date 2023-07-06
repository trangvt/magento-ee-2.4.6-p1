<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule\Condition;

use Magento\Directory\Model\Config\Source\Allregion;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Rule\Model\Condition\Context;
use Magento\Shipping\Model\Config\Source\Allmethods as ShippingAllMethods;
use Magento\Payment\Model\Config\Source\Allmethods as PaymentAllMethods;
use Magento\SalesRule\Model\Rule\Condition\Address as SalesRuleConditionAddress;
use Magento\Directory\Model\CurrencyFactory;

/**
 * Address condition for Purchase Order approval rules
 */
class Address extends SalesRuleConditionAddress
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var TotalsCollector
     */
    private $totalsCollector;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * @param Context $context
     * @param Country $directoryCountry
     * @param Allregion $directoryAllregion
     * @param ShippingAllMethods $shippingAllmethods
     * @param PaymentAllMethods $paymentAllmethods
     * @param CartRepositoryInterface $cartRepository
     * @param TotalsCollector $totalsCollector
     * @param CurrencyFactory $currencyFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Country $directoryCountry,
        Allregion $directoryAllregion,
        ShippingAllMethods $shippingAllmethods,
        PaymentAllMethods $paymentAllmethods,
        CartRepositoryInterface $cartRepository,
        TotalsCollector $totalsCollector,
        CurrencyFactory $currencyFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $directoryCountry,
            $directoryAllregion,
            $shippingAllmethods,
            $paymentAllmethods,
            $data
        );

        $this->cartRepository = $cartRepository;
        $this->totalsCollector = $totalsCollector;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * Retrieve the shipping address from whichever model type was passed into the validate function
     *
     * @param AbstractModel|PurchaseOrderInterface|CartInterface $model
     * @return bool
     * @throws NoSuchEntityException
     */
    public function validate(AbstractModel $model)
    {
        if ($model instanceof PurchaseOrderInterface) {
            /* @var Quote $quote */
            $quote = $this->cartRepository->get($model->getQuoteId());
            /* @var QuoteAddress $model */
            $model = $quote->getShippingAddress();
        } elseif ($model instanceof Quote) {
            $model = $model->getShippingAddress();
        }

        // If the grand_total isn't available in the address, set it from the quote
        if ($this->getAttribute() === 'grand_total' && (int) $model->getGrandTotal() === 0) {
            $model->setGrandTotal($model->getQuote()->getGrandTotal());
        }

        // Convert purchase order currency for validation against rule currency
        if ($this->getCurrencyCode() &&
            ($this->getCurrencyCode() !== $model->getQuote()->getQuoteCurrencyCode())
        ) {
            $originalAmountValue = $model->getData($this->getAttribute());
            $rate = $this->getCurrencyRate($model->getQuote()->getQuoteCurrencyCode(), $this->getCurrencyCode());
            // If there is no currency rate, force purchase order to match rule
            if (!$rate) {
                return true;
            }
            $model->setData($this->getAttribute(), round($originalAmountValue * $rate, 2));
            $result = parent::validate($model);
            $model->setData($this->getAttribute(), $originalAmountValue);
        } else {
            $result = parent::validate($model);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function loadArray($arr)
    {
        parent::loadArray($arr);
        $this->setCurrencyCode($arr['currency_code'] ?? null);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function asArray(array $arrAttributes = [])
    {
        $out = parent::asArray($arrAttributes);
        $out['currency_code'] = $this->getCurrencyCode();

        return $out;
    }

    /**
     * Get currency rate
     *
     * @param string $fromCurrencyCode
     * @param string $toCurrencyCode
     * @return mixed
     */
    private function getCurrencyRate(string $fromCurrencyCode, string $toCurrencyCode)
    {
        $fromCurrency = $this->currencyFactory->create()->load($fromCurrencyCode);
        $toCurrency = $this->currencyFactory->create()->load($toCurrencyCode);

        return $fromCurrency->getAnyRate($toCurrency);
    }
}
