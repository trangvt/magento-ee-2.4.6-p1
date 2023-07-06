<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Plugin\Model\Quote;

use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Weee\Helper\Data as WeeeHelper;

/**
 * Add weee attributes amounts to negotiable quote totals.
 */
class TotalsPlugin
{
    /**
     * @var WeeeHelper
     */
    private $weeeHelper;

    /**
     * @param WeeeHelper $weeeHelper
     */
    public function __construct(
        WeeeHelper $weeeHelper
    ) {
        $this->weeeHelper = $weeeHelper;
    }

    /**
     * Add weee attributes amount to catalog total price without tax.
     *
     * @param Totals $model
     * @param int|float $totalPrice
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetCatalogTotalPriceWithoutTax(Totals $model, $totalPrice, bool $useQuoteCurrency = false)
    {
        $quote = $model->getQuote();
        $weeeAmount = 0;

        if ($this->weeeHelper->isEnabled($quote->getStore())
            && $this->weeeHelper->includeInSubtotal($quote->getStore())) {
            $weeeAmount = $this->getWeeeTotalsWithoutTax($quote, $useQuoteCurrency);
        }

        return $totalPrice + $weeeAmount;
    }

    /**
     * Add weee attributes tax to quote tax value.
     *
     * @param Totals $model
     * @param int|float $totalTax
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetOriginalTaxValue(Totals $model, $totalTax, bool $useQuoteCurrency = false)
    {
        $quote = $model->getQuote();
        $weeeTotalTax = 0;

        if ($this->weeeHelper->isEnabled($quote->getStore())
            && $this->weeeHelper->isTaxable($quote->getStore())
        ) {
            $quoteItems = $quote->getAllVisibleItems();

            /** @var CartItemInterface $quoteItem */
            foreach ($quoteItems as $quoteItem) {
                if ($useQuoteCurrency === true) {
                    $weeeTaxesApplied = $this->weeeHelper->getRowWeeeTaxInclTax($quoteItem)
                        - $this->weeeHelper->getWeeeTaxAppliedRowAmount($quoteItem);
                } else {
                    $weeeTaxesApplied = $this->weeeHelper->getBaseRowWeeeTaxInclTax($quoteItem)
                        - $this->weeeHelper->getBaseWeeeTaxAppliedRowAmount($quoteItem);
                }
                $weeeTotalTax += $weeeTaxesApplied;
            }
        }

        return $weeeTotalTax + $totalTax;
    }

    /**
     * Add weee attributes amount to catalog total price with tax.
     *
     * @param Totals $model
     * @param int|float $totalWithTax
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetCatalogTotalPriceWithTax(Totals $model, $totalWithTax, bool $useQuoteCurrency = false)
    {
        $quote = $model->getQuote();
        $weeeAmount = 0;

        if ($this->weeeHelper->isEnabled($quote->getStore())
            && !($model->isTaxDisplayedWithGrandTotal() || $this->weeeHelper->includeInSubtotal($quote->getStore()))
        ) {
            $weeeAmount = $this->getWeeeTotalsWithoutTax($quote, $useQuoteCurrency);
        }

        return $totalWithTax + $weeeAmount;
    }

    /**
     * Add weee attributes amount to quote subtotal.
     *
     * @param Totals $model
     * @param int|float $totalWithTax
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    public function afterGetSubtotal(Totals $model, $totalWithTax, bool $useQuoteCurrency = false)
    {
        $quote = $model->getQuote();
        $weeeAmount = 0;
        if ($this->weeeHelper->isEnabled($quote->getStore())
            && ($this->weeeHelper->includeInSubtotal($quote->getStore()) || $model->isTaxDisplayedWithGrandTotal())
        ) {
            $weeeAmount = $this->getWeeeTotalsWithoutTax($quote, $useQuoteCurrency);
        }

        return $weeeAmount + $totalWithTax;
    }

    /**
     * Calculate weee attributes amount for all quote items.
     *
     * @param CartInterface $quote
     * @param bool $useQuoteCurrency
     * @return int|float
     */
    private function getWeeeTotalsWithoutTax(CartInterface $quote, bool $useQuoteCurrency)
    {
        $weeeAmount = 0;
        $quoteItems = $quote->getAllVisibleItems();
        /** @var CartItemInterface $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            $weeeApplied = $useQuoteCurrency
                ? $this->weeeHelper->getWeeeTaxAppliedRowAmount($quoteItem)
                : $this->weeeHelper->getBaseWeeeTaxAppliedRowAmount($quoteItem);
            $weeeAmount += $weeeApplied;
        }

        return $weeeAmount;
    }
}
