<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template;

/**
 * Block class that provides template for shipping methods
 */
class ShippingMethods extends Template
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->setTemplate('Magento_CompanyShipping::shipping/methods.phtml');
        parent::_construct();
    }
}
