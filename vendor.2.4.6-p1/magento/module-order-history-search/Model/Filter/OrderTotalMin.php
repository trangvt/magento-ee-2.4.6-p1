<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Class OrderTotalMin.
 *
 * Model for 'Order Total Min' filter for order search filter.
 */
class OrderTotalMin implements FilterInterface
{
    /**
     * @inheritdoc
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection
    {
        $formattedValue = (int)floor((float) $value);

        $ordersCollection->addFieldToFilter(OrderInterface::GRAND_TOTAL, ['gteq' => $formattedValue]);

        return $ordersCollection;
    }
}
