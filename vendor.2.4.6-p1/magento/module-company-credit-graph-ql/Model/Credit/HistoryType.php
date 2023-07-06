<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model\Credit;

/**
 * History type mapper
 */
class HistoryType
{
    /**
     * @var array
     */
    private $creditHistoryType;

    /**
     * @param array $creditHistoryType
     */
    public function __construct(
        array $creditHistoryType = []
    ) {
        $this->creditHistoryType = $creditHistoryType;
    }

    /**
     * Get history type id by name
     *
     * @param string $type
     * @return int
     */
    public function getHistoryTypeId(string $type): int
    {
        return array_search($type, array_column($this->creditHistoryType, 'label', 'value'), false);
    }

    /**
     * Get history type name by id
     *
     * @param int $typeId
     * @return string
     */
    public function getHistoryType(int $typeId): string
    {
        return array_search($typeId, array_column($this->creditHistoryType, 'value', 'label'), false);
    }
}
