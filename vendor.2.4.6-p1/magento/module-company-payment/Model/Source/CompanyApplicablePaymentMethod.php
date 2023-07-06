<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Model\Source;

/**
 *  Provide option for Company Applicable Payment Method.
 */
class CompanyApplicablePaymentMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Option values for B2B applicable payment methods.
     */
    private const B2B_PAYMENT_METHODS_VALUE = 0;
    private const ALL_PAYMENT_METHODS_VALUE = 1;
    private const SELECTED_PAYMENT_METHODS_VALUE = 2;

    /**
     * Get CompanyApplicablePaymentMethod Option Type
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::B2B_PAYMENT_METHODS_VALUE,
                'label' => __('B2B Payment Methods'),
            ],
            [
                'value' => self::ALL_PAYMENT_METHODS_VALUE,
                'label' => __('All Enabled Payment Methods'),
            ],
            [
                'value' => self::SELECTED_PAYMENT_METHODS_VALUE,
                'label' => __('Selected Payment Methods'),
            ],
        ];
    }
}
