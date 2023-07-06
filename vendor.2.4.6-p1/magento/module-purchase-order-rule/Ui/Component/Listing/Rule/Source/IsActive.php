<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrderRule\Ui\Component\Listing\Rule\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * IsActive source for grid
 */
class IsActive implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Enabled'),
                'value' => '1',
            ],
            [
                'label' => __('Disabled'),
                'value' => '0',
            ],
        ];
    }
}
