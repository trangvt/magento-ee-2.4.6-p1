<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Block\Item\Price;

/**
 * Negotiable quote item weee price renderer.
 */
class Renderer extends \Magento\Weee\Block\Item\Price\Renderer
{
    /**
     * @inheritDoc
     */
    public function getUnitDisplayPriceInclTax()
    {
        $quote = $this->getItem()->getQuote();

        $priceExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax(),
            $quote->getStore()
        );
        $taxValue = $this->priceCurrency->convert(
            $this->getTaxValue(),
            $quote->getStore()
        );

        $priceInclTax = $priceExclTax + $taxValue;

        if ($this->weeeHelper->isEnabled($this->getStoreId()) && $this->getIncludeWeeeFlag()) {
            $priceInclTax += $this->weeeHelper->getWeeeTaxInclTax($this->getItem());
        }

        return $priceInclTax;
    }

    /**
     * @inheritDoc
     */
    public function getFinalUnitDisplayPriceInclTax()
    {
        $quote = $this->getItem()->getQuote();

        $priceExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax(),
            $quote->getStore()
        );
        $taxValue = $this->priceCurrency->convert(
            $this->getTaxValue(),
            $quote->getStore()
        );

        $priceInclTax = $priceExclTax + $taxValue;

        if ($this->weeeHelper->isEnabled($this->getStoreId())) {
            $priceInclTax += $this->weeeHelper->getWeeeTaxInclTax($this->getItem());
        }

        return $priceInclTax;
    }

    /**
     * @inheritDoc
     */
    public function getUnitDisplayPriceExclTax()
    {
        $quote = $this->getItem()->getQuote();

        $priceExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax(),
            $quote->getStore()
        );

        if ($this->weeeHelper->isEnabled($this->getStoreId()) && $this->getIncludeWeeeFlag()) {
            $priceExclTax += $this->weeeHelper->getWeeeTaxAppliedAmount($this->getItem());
        }

        return $priceExclTax;
    }

    /**
     * @inheritDoc
     */
    public function getFinalUnitDisplayPriceExclTax()
    {
        $quote = $this->getItem()->getQuote();

        $priceExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax(),
            $quote->getStore()
        );

        if ($this->weeeHelper->isEnabled($this->getStoreId())) {
            $priceExclTax += $this->weeeHelper->getWeeeTaxAppliedAmount($this->getItem());
        }

        return $priceExclTax;
    }

    /**
     * @inheritDoc
     */
    public function getRowDisplayPriceInclTax()
    {
        $quote = $this->getItem()->getQuote();
        $qty = $this->getItem()->getQty();

        $rowTotalExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax() * $qty,
            $quote->getStore()
        );
        $rowTaxValue = $this->priceCurrency->convert(
            $this->getTaxValue() * $qty,
            $quote->getStore()
        );

        $rowTotalInclTax = $rowTotalExclTax + $rowTaxValue;

        if ($this->weeeHelper->isEnabled($this->getStoreId()) && $this->getIncludeWeeeFlag()) {
            $rowTotalInclTax += $this->weeeHelper->getRowWeeeTaxInclTax($this->getItem());
        }

        return $rowTotalInclTax;
    }

    /**
     * @inheritDoc
     */
    public function getFinalRowDisplayPriceInclTax()
    {
        $quote = $this->getItem()->getQuote();
        $qty = $this->getItem()->getQty();

        $rowTotalExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax() * $qty,
            $quote->getStore()
        );
        $rowTaxValue = $this->priceCurrency->convert(
            $this->getTaxValue() * $qty,
            $quote->getStore()
        );

        $rowTotalInclTax = $rowTotalExclTax + $rowTaxValue;

        if ($this->weeeHelper->isEnabled($this->getStoreId())) {
            $rowTotalInclTax += $this->weeeHelper->getRowWeeeTaxInclTax($this->getItem());
        }

        return $rowTotalInclTax;
    }

    /**
     * @inheritDoc
     */
    public function getRowDisplayPriceExclTax()
    {
        $quote = $this->getItem()->getQuote();
        $qty = $this->getItem()->getQty();

        $rowTotalExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax() * $qty,
            $quote->getStore()
        );

        if ($this->weeeHelper->isEnabled($this->getStoreId()) && $this->getIncludeWeeeFlag()) {
            $rowTotalExclTax += $this->weeeHelper->getWeeeTaxAppliedRowAmount($this->getItem());
        }

        return $rowTotalExclTax;
    }

    /**
     * @inheritDoc
     */
    public function getFinalRowDisplayPriceExclTax()
    {
        $quote = $this->getItem()->getQuote();
        $qty = $this->getItem()->getQty();

        $rowTotalExclTax = $this->priceCurrency->convert(
            $this->getItemDisplayPriceExclTax() * $qty,
            $quote->getStore()
        );

        if ($this->weeeHelper->isEnabled($this->getStoreId())) {
            $rowTotalExclTax += $this->weeeHelper->getWeeeTaxAppliedRowAmount($this->getItem());
        }

        return $rowTotalExclTax;
    }

    /**
     * @inheritDoc
     */
    public function getItemDisplayPriceExclTax(): float
    {
        $item = $this->getItem();
        $price = ($item->getExtensionAttributes()
            && $item->getExtensionAttributes()->getNegotiableQuoteItem()
            && $item->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalPrice())
            ? $item->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalPrice()
            : 0;

        return (float)$price;
    }

    /**
     * Get tax value in base currency.
     *
     * @return float
     */
    private function getTaxValue(): float
    {
        $item = $this->getItem();
        $tax = ($item->getExtensionAttributes()
            && $item->getExtensionAttributes()->getNegotiableQuoteItem()
            && $item->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalTaxAmount())
            ? $item->getExtensionAttributes()->getNegotiableQuoteItem()->getOriginalTaxAmount()
            : 0;

        return (float)$tax;
    }
}
