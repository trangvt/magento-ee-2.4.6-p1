<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Class OrderStatus.
 *
 * Model for 'Order Status' filter for order search filter.
 */
class OrderStatus implements FilterInterface
{
    /**
     * @inheritdoc
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection
    {
        $ordersCollection->addFieldToFilter(OrderInterface::STATUS, ['eq' => $value]);

        return $ordersCollection;
    }
}
