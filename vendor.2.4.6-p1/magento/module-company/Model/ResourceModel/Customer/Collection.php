<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\ResourceModel\Customer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Company collection
 */
class Collection extends AbstractCollection
{
    /**
     * Standard collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Company\Model\Customer::class, \Magento\Company\Model\ResourceModel\Customer::class);
    }
}
