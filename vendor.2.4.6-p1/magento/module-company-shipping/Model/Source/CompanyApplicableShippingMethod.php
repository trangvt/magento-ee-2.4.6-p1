<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for company shipping method types
 */
class CompanyApplicableShippingMethod implements OptionSourceInterface
{
    /**
     * Option values for B2B applicable shipping methods.
     */
    const B2B_SHIPPING_METHODS_VALUE = 0;
    const ALL_SHIPPING_METHODS_VALUE = 1;
    const SELECTED_SHIPPING_METHODS_VALUE = 2;

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::B2B_SHIPPING_METHODS_VALUE,
                'label' => __('B2B Shipping Methods'),
            ],
            [
                'value' => self::ALL_SHIPPING_METHODS_VALUE,
                'label' => __('All Enabled Shipping Methods'),
            ],
            [
                'value' => self::SELECTED_SHIPPING_METHODS_VALUE,
                'label' => __('Selected Shipping Methods'),
            ],
        ];
    }
}
