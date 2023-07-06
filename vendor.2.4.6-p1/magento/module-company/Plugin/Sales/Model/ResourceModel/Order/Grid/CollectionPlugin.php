<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Plugin\Sales\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

/**
 * Add company order extension attribute to order grid collection.
 */
class CollectionPlugin
{
    /**
     * Flag to mark collections with joined company_order table.
     */
    private const JOINED_COMPANY_ORDER_FLAG = 'joined_company_order';

    /**
     * Add company order extension attribute to order grid collection before loading.
     *
     * @param OrderGridCollection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoadWithFilter(
        OrderGridCollection $subject,
        $printQuery = false,
        $logQuery = false
    ): array {
        if (!$subject->getFlag(self::JOINED_COMPANY_ORDER_FLAG)) {
            $subject->getSelect()
                ->joinLeft(
                    ['company_order' => $subject->getTable('company_order_entity')],
                    'main_table.entity_id = company_order.order_id',
                    ['company_name']
                );
            $subject->setFlag(self::JOINED_COMPANY_ORDER_FLAG, 1);
        }

        return [$printQuery, $logQuery];
    }

    /**
     * Add company order extension attribute to order grid collection before getting size.
     *
     * @param OrderGridCollection $subject
     * @return void
     */
    public function beforeGetSelectCountSql(OrderGridCollection $subject): void
    {
        if (!$subject->getFlag(self::JOINED_COMPANY_ORDER_FLAG)) {
            $subject->getSelect()
                ->joinLeft(
                    ['company_order' => $subject->getTable('company_order_entity')],
                    'main_table.entity_id = company_order.order_id',
                    ['company_name']
                );
            $subject->setFlag(self::JOINED_COMPANY_ORDER_FLAG, 1);
        }
    }
}
