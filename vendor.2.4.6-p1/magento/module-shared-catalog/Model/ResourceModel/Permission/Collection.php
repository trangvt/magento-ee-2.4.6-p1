<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Model\ResourceModel\Permission;

/**
 * Permission collection for displaying category permissions in categories tree.
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize collection.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\SharedCatalog\Model\Permission::class,
            \Magento\SharedCatalog\Model\ResourceModel\Permission::class
        );
    }
}
