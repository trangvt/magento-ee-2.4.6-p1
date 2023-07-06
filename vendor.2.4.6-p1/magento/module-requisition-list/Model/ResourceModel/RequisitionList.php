<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\RequisitionList\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Requisition List resource model
 */
class RequisitionList extends AbstractDb
{
    /**
     * Requisition List table
     *
     * @var string
     */
    private $requisitionListTable = 'requisition_list';

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init($this->requisitionListTable, 'entity_id');
    }
}
