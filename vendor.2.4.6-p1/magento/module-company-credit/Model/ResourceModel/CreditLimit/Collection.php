<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Model\ResourceModel\CreditLimit;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * CreditLimit collection.
 */
class Collection extends AbstractCollection
{
    /**
     * Standard collection initialization.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\CompanyCredit\Model\CreditLimit::class,
            \Magento\CompanyCredit\Model\ResourceModel\CreditLimit::class
        );
    }
}
