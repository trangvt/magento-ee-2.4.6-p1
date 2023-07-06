<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Plugin\Customer\Model\ResourceModel\Online\Grid;

use Magento\Customer\Model\ResourceModel\Online\Grid\Collection;

/**
 * Plugin for customer now online grid collection to display company column.
 */
class CollectionPlugin
{
    /**
     * Before Load plugin.
     *
     * @param Collection $subject
     * @param bool $printQuery [optional]
     * @param bool $logQuery [optional]
     * @return array
     */
    public function beforeLoad(
        Collection $subject,
        $printQuery = false,
        $logQuery = false
    ) {
        if (!$subject->isLoaded()) {
            $subject->getSelect()
                ->joinLeft(
                    ['company_customer' => $subject->getTable('company_advanced_customer_entity')],
                    'main_table.customer_id = company_customer.customer_id',
                    ['company_id']
                );
            $subject->getSelect()
                ->joinLeft(
                    ['company' => $subject->getTable('company')],
                    'company.entity_id = company_customer.company_id',
                    ['company_name']
                );
        }
        return [$printQuery, $logQuery];
    }
}
