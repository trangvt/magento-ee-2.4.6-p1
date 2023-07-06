<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Interface FilterInterface
 *
 * @api
 */
interface FilterInterface
{
    /**
     * Apply filter for provided collection
     *
     * @param Collection $ordersCollection
     * @param mixed $value
     *
     * @return Collection
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection;
}
