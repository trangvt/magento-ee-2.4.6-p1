<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\CompanyShipping\Block\Adminhtml\System\Config\ShippingMethods;

/**
 * Block class that provides shipping methods fieldset
 */
class ShippingMethodsFieldset extends Fieldset
{
    /**
     * @inheritdoc
     */
    protected function _getFooterHtml($element)
    {
        return parent::_getFooterHtml($element)
            . $this->getLayout()
                ->createBlock(ShippingMethods::class)
                ->toHtml();
    }
}
