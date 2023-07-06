<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Model\ResourceModel;

/**
 * Class CompanyPaymentMethod.
 */
class CompanyPaymentMethod extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected $_useIsObjectNew = true;

    /**
     * {@inheritdoc}
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Company payment table.
     *
     * @var string
     */
    private $companyPaymentTable = 'company_payment';

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init($this->companyPaymentTable, 'company_id');
    }
}
