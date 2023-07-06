<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class DeleteButton
 *
 * @package Magento\Customer\Block\Adminhtml\Edit
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->getRequest()->getParam('id')) {
            return [];
        }
        $data = [
            'label' => __('Delete Company'),
            'class' => 'delete',
            'id' => 'company-edit-delete-button',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Company/edit/post-wrapper' => ['url' => $this->getDeleteUrl()],
                ]
            ],
            'on_click' => '',
            'sort_order' => 20,
            'aclResource' => 'Magento_Company::delete'
        ];
        return $data;
    }

    /**
     * Get delete url.
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $companyId = $this->getRequest()->getParam('id');
        return $this->getUrl('*/*/delete', ['id' => $companyId]);
    }
}
