<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\PurchaseOrderRule\Model\AppliedRuleApprover;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover as ResourceAppliedRuleApprover;

/**
 * Applied rule approver collection
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
            AppliedRuleApprover::class,
            ResourceAppliedRuleApprover::class
        );
    }
}
