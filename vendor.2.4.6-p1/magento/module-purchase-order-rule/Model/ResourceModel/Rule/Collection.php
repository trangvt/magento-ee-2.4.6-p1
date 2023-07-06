<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\PurchaseOrderRule\Model\Rule;
use Magento\PurchaseOrderRule\Model\ResourceModel\Rule as ResourceRule;

/**
 * Rule collection
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
            Rule::class,
            ResourceRule::class
        );
    }

    /**
     * Filter the collection by whom the rules applies to
     *
     * @param array $roleIds
     */
    public function addAppliesToFilter(array $roleIds)
    {
        /**
         * Join onto the relational table and only select all rules which have a matching assigned role ID. If they're
         * specifying role IDs we also need to retrieve rules which apply to all roles IDs.
         */
        $this->getSelect()->joinLeft(
            ['at' => $this->getTable('purchase_order_rule_applies_to')],
            '`main_table`.`rule_id` = `at`.`rule_id`',
            []
        )->where('`at`.`role_id` IN(?) OR `main_table`.`applies_to_all` = 1', $roleIds)
        ->group('main_table.rule_id');
    }
}
