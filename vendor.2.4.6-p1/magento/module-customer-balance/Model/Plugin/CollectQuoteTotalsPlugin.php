<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Plugin;

use Magento\Quote\Model\Quote;

class CollectQuoteTotalsPlugin
{
    /**
     * Reset quote used customer balance amount
     *
     * @param \Magento\Quote\Model\Quote\TotalsCollector $subject
     * @param Quote $quote
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollectQuoteTotals(
        \Magento\Quote\Model\Quote\TotalsCollector $subject,
        Quote $quote
    ) {
        $quote->setBaseCustomerBalAmountUsed(0);
        $quote->setCustomerBalanceAmountUsed(0);
    }
}
