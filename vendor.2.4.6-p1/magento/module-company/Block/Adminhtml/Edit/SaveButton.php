<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Ui\Component\Control\Container;

/**
 * Class SaveButton.
 *
 * @package Magento\Company\Block\Adminhtml\Edit
 */
class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'company_form.company_form',
                                'actionName' => 'save',
                                'params' => [
                                    false
                                ]
                            ]
                        ]
                    ]
                ],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
            'aclResource' => 'Magento_Company::manage'
        ];

        return $data;
    }

    /**
     * Get options.
     *
     * @return array
     */
    private function getOptions()
    {
        return [
            [
                'id_hard' => 'save_and_close',
                'label' => __('Save & Close'),
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'company_form.company_form',
                                    'actionName' => 'save',
                                    'params' => [
                                        true
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
            ]
        ];
    }
}
