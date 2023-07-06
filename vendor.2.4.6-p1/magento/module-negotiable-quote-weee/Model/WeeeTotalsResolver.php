<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Model;

use Magento\Framework\DataObject;
use Magento\NegotiableQuote\Helper\Quote as NegotiableQuoteHelper;
use Magento\Weee\Helper\Data as WeeeHelper;

/**
 * Add Weee totals to other negotiable quote totals.
 */
class WeeeTotalsResolver
{
    /**
     * @var NegotiableQuoteHelper
     */
    private $negotiableQuoteHelper;

    /**
     * @var WeeeHelper
     */
    private $weeeHelper;

    /**
     * @var array
     */
    private $quoteTotals = [];

    /**
     * @param NegotiableQuoteHelper $negotiableQuoteHelper
     * @param WeeeHelper $weeeHelper
     */
    public function __construct(
        NegotiableQuoteHelper $negotiableQuoteHelper,
        WeeeHelper $weeeHelper
    ) {
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
        $this->weeeHelper = $weeeHelper;
    }

    /**
     * Add Weee totals to other negotiable quote totals.
     *
     * @param array $totals
     * @param bool $useBaseCurrency
     * @return array
     */
    public function updateTotals(array $totals, bool $useBaseCurrency): array
    {
        $quote = $this->negotiableQuoteHelper->resolveCurrentQuote(true);

        if ($this->weeeHelper->isEnabled($quote->getStore())) {
            if (!isset($this->quoteTotals[$quote->getId()])) {
                $quoteItems = $quote->getAllVisibleItems();

                if ($useBaseCurrency === true) {
                    $weeeTotal = $this->weeeHelper->getBaseTotalAmounts($quoteItems, $quote->getStore());
                } else {
                    $weeeTotal = $this->weeeHelper->getTotalAmounts($quoteItems, $quote->getStore());
                }
                $this->quoteTotals[$quote->getId()] = $weeeTotal;
            }

            if (!empty($this->quoteTotals[$quote->getId()])) {
                $weeeSubtotal = [
                    'code' => 'quote_weee',
                    'value' => $this->quoteTotals[$quote->getId()],
                    'label' => __('FPT'),
                    'class' => '',
                ];

                $totals = $this->addWeeeToSubtotal($totals, $weeeSubtotal);
                $totals = $this->addWeeToOriginalSubtotal($totals, $weeeSubtotal);
            }
        }

        return $totals;
    }

    /**
     * Add Weee total to subtotal.
     *
     * @param array $totals
     * @param array $weeeSubtotal
     * @return array
     */
    private function addWeeeToSubtotal(array $totals, array $weeeSubtotal): array
    {
        $totalKeys = array_keys($totals);
        $index = array_search('grand_total', $totalKeys);
        $pos = false === $index ? count($totals) : $index;

        $totals = array_merge(
            array_slice($totals, 0, $pos),
            ['quote_weee' => new DataObject($weeeSubtotal)],
            array_slice($totals, $pos)
        );

        return $totals;
    }

    /**
     * Add Wee total to original subtotal.
     *
     * @param array $totals
     * @param array $weeeSubtotal
     * @return array
     */
    private function addWeeToOriginalSubtotal(array $totals, array $weeeSubtotal): array
    {
        $catalogPriceSubtotals = $totals['catalog_price']->getData('subtotals');
        $catalogPriceSubtotals['catalog_weee'] = $weeeSubtotal;
        $totals['catalog_price']->setSubtotals($catalogPriceSubtotals);

        return $totals;
    }
}
