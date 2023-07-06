<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Weee\Helper\Data as WeeeHelper;

/**
 * Get quote items Weee attributes prices.
 */
class WeeeDataResolver
{
    /**
     * @var WeeeHelper
     */
    private $weeeHelper;

    /**
     * @var TaxConfig
     */
    private $taxConfig;

    /**
     * @var bool
     */
    private $isWeeeAttributeVisible;

    /**
     * @var bool
     */
    private $isWeeeAttributeWithTaxVisible;

    /**
     * @param WeeeHelper $weeeHelper
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        WeeeHelper $weeeHelper,
        TaxConfig $taxConfig
    ) {
        $this->weeeHelper = $weeeHelper;
        $this->taxConfig = $taxConfig;
    }

    /**
     * Check if Weee attribute without tax applied should be visible on the page.
     *
     * @param CartInterface $quote
     * @return bool
     */
    public function isWeeeAttributeWithoutTaxVisible(CartInterface $quote): bool
    {
        if ($this->isWeeeAttributeVisible === null) {
            $this->isWeeeAttributeVisible = false;

            if (!$this->weeeHelper->isEnabled($quote->getStoreId())) {
                return $this->isWeeeAttributeVisible;
            }

            foreach ($quote->getAllvisibleItems() as $quoteItem) {
                $baseWeeeTaxAppliedRowAmount = $this->getQuoteItemBaseWeeeAmountWithoutTax($quoteItem);
                if ($baseWeeeTaxAppliedRowAmount !== (float)0) {
                    $this->isWeeeAttributeVisible = true;
                    break;
                }
            }
        }

        return $this->isWeeeAttributeVisible;
    }

    /**
     * Return Quote Item Base Weee Amount Without Tax.
     *
     * @param Item $item
     * @return float
     */
    public function getQuoteItemBaseWeeeAmountWithoutTax(Item $item): float
    {
        return (float)$this->weeeHelper->getBaseWeeeTaxAppliedRowAmount($item);
    }

    /**
     * Check if Weee attribute with tax should be visible on the page.
     *
     * @param CartInterface $quote
     * @return bool
     */
    public function isWeeeAttributeWithTaxVisible(CartInterface $quote): bool
    {
        if ($this->isWeeeAttributeWithTaxVisible === null) {
            $this->isWeeeAttributeWithTaxVisible = false;
            $storeId = $quote->getStoreId();
            if (!($this->weeeHelper->isEnabled($storeId) && $this->weeeHelper->isTaxable($storeId))) {
                return $this->isWeeeAttributeWithTaxVisible;
            }

            foreach ($quote->getAllvisibleItems() as $quoteItem) {
                $baseWeeeTaxAppliedRowAmount = $this->getQuoteItemBaseWeeeAmountWithTax($quoteItem);
                if ($baseWeeeTaxAppliedRowAmount !== (float)0) {
                    $this->isWeeeAttributeWithTaxVisible = true;
                    break;
                }
            }
        }

        return $this->isWeeeAttributeWithTaxVisible;
    }

    /**
     * Return Quote Item Base Weee Amount With Tax.
     *
     * @param Item $item
     * @return float
     */
    public function getQuoteItemBaseWeeeAmountWithTax(Item $item): float
    {
        return (float)$this->weeeHelper->getBaseRowWeeeTaxInclTax($item);
    }

    /**
     * Return Item Subtotal With Tax.
     *
     * @param Item $item
     * @return float
     */
    public function getItemSubtotalTaxValue(Item $item): float
    {
        $subtotal = $this->isTaxDisplayedWithSubtotal($item->getStoreId())
            ? $item->getBaseRowTotal() + $item->getBaseTaxAmount()
            : $item->getBaseRowTotal();

        if ($this->weeeHelper->isEnabled($item->getStoreId())
            && ($this->isTaxDisplayedWithSubtotal($item->getStoreId())
                || $this->weeeHelper->includeInSubtotal($item->getStoreId())
            )
        ) {
            $subtotal += $this->isItemWeeeWithTax($item->getStoreId())
                ? $this->getQuoteItemBaseWeeeAmountWithTax($item)
                : $this->getQuoteItemBaseWeeeAmountWithoutTax($item);
        }

        return (float)$subtotal;
    }

    /**
     * Check if item weee attribute should be shown with tax.
     *
     * @param int $storeId
     * @return bool
     */
    private function isItemWeeeWithTax(int $storeId): bool
    {
        return $this->weeeHelper->isTaxable($storeId)
            && $this->isTaxDisplayedWithSubtotal($storeId);
    }

    /**
     * Check if subtotal should be displayed with tax.
     *
     * @param int $storeId
     * @return bool
     */
    private function isTaxDisplayedWithSubtotal(int $storeId): bool
    {
        return $this->taxConfig->displaySalesSubtotalInclTax($storeId)
            || $this->taxConfig->displaySalesSubtotalBoth($storeId);
    }
}
