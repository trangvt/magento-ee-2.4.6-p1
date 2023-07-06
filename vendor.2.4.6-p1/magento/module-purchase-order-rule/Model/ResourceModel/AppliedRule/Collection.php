<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\PurchaseOrderRule\Model\AppliedRule;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRule as ResourceAppliedRule;

/**
 * Applied rule collection
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            AppliedRule::class,
            ResourceAppliedRule::class
        );
    }
}
