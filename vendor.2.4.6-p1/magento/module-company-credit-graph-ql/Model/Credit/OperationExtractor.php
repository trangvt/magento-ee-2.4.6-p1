<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model\Credit;

use Magento\CompanyCredit\Model\HistoryInterface;

/**
 * Extract operation details
 */
class OperationExtractor
{
    /**
     * @var HistoryType
     */
    private $historyType;

    /**
     * @var OperationUser
     */
    private $operationUser;

    /**
     * @var Balance
     */
    private $balance;

    /**
     * @param HistoryType $historyType
     * @param OperationUser $operationUser
     * @param Balance $balance
     */
    public function __construct(
        HistoryType $historyType,
        OperationUser $operationUser,
        Balance $balance
    ) {
        $this->historyType = $historyType;
        $this->operationUser = $operationUser;
        $this->balance = $balance;
    }

    /**
     * Extract credit history data
     *
     * @param HistoryInterface $creditOperation
     * @return array
     */
    public function extractOperation(HistoryInterface $creditOperation): array
    {
        return [
            'amount' => $this->balance->formatData(
                $creditOperation->getCurrencyOperation(),
                (float)$creditOperation->getAmount()
            ),
            'date' => $creditOperation->getDatetime(),
            'custom_reference_number' => $creditOperation->getCustomReferenceNumber(),
            'type' => $this->historyType->getHistoryType((int)$creditOperation->getType()),
            'updated_by' => [
                'name' => $this->operationUser->getUserName(
                    (int)$creditOperation->getUserType(),
                    (int)$creditOperation->getUserId()
                ),
                'type' => $this->operationUser->getUserType((int)$creditOperation->getUserType())
            ],
            'balance' => $this->balance->getBalance($creditOperation)
        ];
    }
}
