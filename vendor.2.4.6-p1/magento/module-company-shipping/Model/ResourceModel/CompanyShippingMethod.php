<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * CompanyShippingMethod resource
 */
class CompanyShippingMethod extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_useIsObjectNew = true;
        $this->_isPkAutoIncrement = false;
        $this->_init('company_shipping', 'company_id');
    }
}
