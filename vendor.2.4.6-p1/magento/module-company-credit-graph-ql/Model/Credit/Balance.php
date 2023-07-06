<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model\Credit;

use Magento\CompanyCredit\Model\HistoryInterface;

/**
 * Credit history balance
 */
class Balance
{
    /**
     * Get operation balance data
     *
     * @param HistoryInterface $creditOperation
     * @return array[]
     */
    public function getBalance(HistoryInterface $creditOperation): array
    {
        return [
            'outstanding_balance' => $this->formatData(
                $creditOperation->getCurrencyOperation(),
                (float)$creditOperation->getBalance()
            ),
            'available_credit' => $this->formatData(
                $creditOperation->getCurrencyOperation(),
                (float)$creditOperation->getAvailableLimit()
            ),
            'credit_limit' => $this->formatData(
                $creditOperation->getCurrencyOperation(),
                (float)$creditOperation->getCreditLimit()
            )
        ];
    }

    /**
     * Format credit response data
     *
     * @param string $currency
     * @param float $value
     * @return array
     */
    public function formatData(string $currency, float $value): array
    {
        return [
            'currency' => $currency,
            'value' => $value
        ];
    }
}
