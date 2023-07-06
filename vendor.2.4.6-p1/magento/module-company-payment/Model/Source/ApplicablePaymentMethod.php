<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Model\Source;

/**
 * Provide option for Applicable Payment Methods.
 */
class ApplicablePaymentMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get ApplicablePaymentMethod Option Type
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('All Payment Methods'),
            ],
            [
                'value' => 1,
                'label' => __('Selected Payment Methods'),
            ],
        ];
    }
}
