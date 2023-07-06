<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Model class that provides selection of shipping methods scope
 */
class ApplicableShippingMethod implements OptionSourceInterface
{
    /**
     * Option values for B2B applicable shipping methods.
     */
    const ALL_SHIPPING_METHODS_VALUE = 0;
    const SELECTED_SHIPPING_METHODS_VALUE = 1;

    /**
     * Returns shipping methods type source data
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ALL_SHIPPING_METHODS_VALUE,
                'label' => __('All Shipping Methods'),
            ],
            [
                'value' => self::SELECTED_SHIPPING_METHODS_VALUE,
                'label' => __('Selected Shipping Methods'),
            ]
        ];
    }
}
