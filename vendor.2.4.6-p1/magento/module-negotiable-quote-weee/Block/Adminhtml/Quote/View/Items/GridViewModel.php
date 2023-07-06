<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Block\Adminhtml\Quote\View\Items;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\NegotiableQuote\Helper\Quote as QuoteHelper;
use Magento\NegotiableQuoteWeee\Model\WeeeDataResolver;
use Magento\Quote\Model\Quote\Item;

/**
 * ViewModel for Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Items\Grid to show weee attributes data on grid.
 */
class GridViewModel implements ArgumentInterface
{
    /**
     * @var WeeeDataResolver
     */
    private $weeeDataResolver;

    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param WeeeDataResolver $weeeDataResolver
     * @param PriceCurrencyInterface $priceCurrency
     * @param QuoteHelper $quoteHelper
     */
    public function __construct(
        WeeeDataResolver $weeeDataResolver,
        PriceCurrencyInterface $priceCurrency,
        QuoteHelper $quoteHelper
    ) {
        $this->weeeDataResolver = $weeeDataResolver;
        $this->priceCurrency = $priceCurrency;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * Check if weee attribute without tax visible.
     *
     * @return bool
     */
    public function isWeeeAttributeWithoutTaxVisible(): bool
    {
        $quote = $this->quoteHelper->resolveCurrentQuote();

        return $this->weeeDataResolver->isWeeeAttributeWithoutTaxVisible($quote);
    }

    /**
     * Get formatted quote item base weee attributes amount without tax.
     *
     * @param Item $item
     * @return string
     */
    public function getFormattedQuoteItemBaseWeeeAmountWithoutTax(Item $item): string
    {
        return $this->priceCurrency->format(
            $this->weeeDataResolver->getQuoteItemBaseWeeeAmountWithoutTax($item),
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $item->getQuote()->getCurrency()->getBaseCurrencyCode()
        );
    }

    /**
     * Check if weee attribute with tax visible.
     *
     * @return bool
     */
    public function isWeeeAttributeWithTaxVisible(): bool
    {
        $quote = $this->quoteHelper->resolveCurrentQuote();

        return $this->weeeDataResolver->isWeeeAttributeWithTaxVisible($quote);
    }

    /**
     * Get formatted quote item base weee attributes amount with tax.
     *
     * @param Item $item
     * @return string
     */
    public function getFormattedQuoteItemBaseWeeeAmountWithTax(Item $item): string
    {
        return $this->priceCurrency->format(
            $this->weeeDataResolver->getQuoteItemBaseWeeeAmountWithTax($item),
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $item->getQuote()->getCurrency()->getBaseCurrencyCode()
        );
    }

    /**
     * Get quote item subtotal tax value.
     *
     * @param Item $item
     * @return string
     */
    public function getItemSubtotalTaxValue(Item $item): string
    {
        return $this->priceCurrency->format(
            $this->weeeDataResolver->getItemSubtotalTaxValue($item),
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $item->getQuote()->getCurrency()->getBaseCurrencyCode()
        );
    }

    /**
     * Returns colspan for table footer.
     *
     * @return int
     */
    public function getColspan(): int
    {
        $colspan = 11;
        $colspan = $this->isWeeeAttributeWithoutTaxVisible() ? ++$colspan : $colspan;
        $colspan = $this->isWeeeAttributeWithTaxVisible() ? ++$colspan : $colspan;

        return $colspan;
    }
}
