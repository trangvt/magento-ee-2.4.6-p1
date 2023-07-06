<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\ResourceModel\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Factory class to create company collection instance
 */
class CollectionFactory implements \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface
{
    /**
     * Object Manager instance.
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager = null;

    /**
     * Instance name to create.
     *
     * @var string
     */
    private $instanceName = \Magento\Sales\Model\ResourceModel\Order\Collection::class;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structure;

    /**
     * @var \Magento\Company\Api\StatusServiceInterface
     */
    private $moduleConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * CollectionFactory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Company\Model\Company\Structure $structure
     * @param \Magento\Company\Api\StatusServiceInterface $moduleConfig
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Company\Model\Company\Structure $structure,
        \Magento\Company\Api\StatusServiceInterface $moduleConfig,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->objectManager = $objectManager;
        $this->structure = $structure;
        $this->moduleConfig = $moduleConfig;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function create($customerId = null)
    {
        $collection = $this->objectManager->create($this->instanceName);
        $collection->getSelect()
            ->joinLeft(
                ['company_order' => $collection->getTable('company_order_entity')],
                'main_table.entity_id = company_order.order_id',
                ['company_id']
            );

        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            $companyId = $companyAttributes ? $companyAttributes->getCompanyId() : null;
            if (!empty($companyId)) {
                $customerIds = [];
                if ($this->moduleConfig->isActive()) {
                    $customerIds = $this->structure->getAllowedChildrenIds($customerId);
                }
                $customerIds[] = $customerId;
                $collection->getSelect()
                    ->where('main_table.customer_id IN(?)', $customerIds)
                    ->where('(company_order.company_id = ?', $companyId)
                    ->orWhere('main_table.customer_id = ? AND company_order.company_id IS NULL)', $customerId);
            } else {
                $collection->addFieldToFilter('company_order.company_id', ['null' => true]);
                $collection->addFieldToFilter('customer_id', $customerId);
            }
        }

        return $collection;
    }
}
