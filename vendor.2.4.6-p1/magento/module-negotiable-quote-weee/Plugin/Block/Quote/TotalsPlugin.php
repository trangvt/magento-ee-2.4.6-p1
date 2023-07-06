<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteWeee\Plugin\Block\Quote;

use Magento\NegotiableQuote\Block\Quote\Totals as TotalsBlock;
use Magento\NegotiableQuoteWeee\Model\WeeeTotalsResolver;

/**
 * Show FPT attribute on negotiable quote page.
 */
class TotalsPlugin
{
    /**
     * @var WeeeTotalsResolver
     */
    private $weeeTotalsResolver;

    /**
     * @param WeeeTotalsResolver $weeeTotalsResolver
     */
    public function __construct(
        WeeeTotalsResolver $weeeTotalsResolver
    ) {
        $this->weeeTotalsResolver = $weeeTotalsResolver;
    }

    /**
     * Add Weee totals.
     *
     * @param TotalsBlock $block
     * @param array $totals
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTotals(TotalsBlock $block, array $totals): array
    {
        return $this->weeeTotalsResolver->updateTotals($totals, false);
    }
}
