<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Controller\Order;

use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Sales\Model\Order as SalesOrder;

/**
 * Controller class that handles order view permissions
 */
class OrderViewAuthorization implements OrderViewAuthorizationInterface
{
    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structure;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $orderConfig;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Company\Api\Data\CompanyOrderInterfaceFactory
     */
    private $companyOrderFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Order
     */
    private $companyOrderResource;

    /**
     * @param \Magento\Company\Model\Company\Structure $structure
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Company\Api\Data\CompanyOrderInterfaceFactory $companyOrderFactory
     * @param \Magento\Company\Model\ResourceModel\Order $companyOrderResource
     */
    public function __construct(
        \Magento\Company\Model\Company\Structure $structure,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Company\Api\Data\CompanyOrderInterfaceFactory $companyOrderFactory,
        \Magento\Company\Model\ResourceModel\Order $companyOrderResource
    ) {
        $this->structure = $structure;
        $this->orderConfig = $orderConfig;
        $this->userContext = $userContext;
        $this->customerRepository = $customerRepository;
        $this->companyOrderFactory = $companyOrderFactory;
        $this->companyOrderResource = $companyOrderResource;
    }

    /**
     * @inheritdoc
     */
    public function canView(SalesOrder $order)
    {
        $availableStatuses = $this->orderConfig->getVisibleOnFrontStatuses();
        $hasViewableOnFrontendStatus = in_array($order->getStatus(), $availableStatuses, true);

        if (!$hasViewableOnFrontendStatus) {
            return false;
        }

        $customerId = $this->userContext->getUserId();

        $orderCompanyAttributes = $this->getCompanyOrderEntityBySalesOrder($order);
        $isACompanyOrder = (bool) $orderCompanyAttributes->getOrderId();

        // if not a company order, simply compare customer ids; always allow a customer to view their own personal order
        if (!$isACompanyOrder) {
            return ((int) $order->getCustomerId()) === $customerId;
        }

        $customer = $this->customerRepository->getById($customerId);
        $customerCompanyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();

        // do not allow a customer to view an order placed in a different company even if they themselves placed it
        if ($customerCompanyAttributes->getCompanyId() !== $orderCompanyAttributes->getCompanyId()) {
            return false;
        }

        // allow to view if current customer's position in company hierarchy is above the customer who placed the order
        $allowedChildIds = $this->structure->getAllowedChildrenIds($customerId);
        $allowedChildIds[] = $customerId;

        return $order->getId() && $order->getCustomerId() && in_array($order->getCustomerId(), $allowedChildIds);
    }

    /**
     * Get company order entity by sales order entity
     *
     * @param SalesOrder $order
     * @return CompanyOrderInterface
     */
    private function getCompanyOrderEntityBySalesOrder(SalesOrder $order)
    {
        /** @var CompanyOrderInterface $orderCompanyAttributes */
        $orderCompanyAttributes = $this->companyOrderFactory->create();
        $this->companyOrderResource->load($orderCompanyAttributes, $order->getId(), CompanyOrderInterface::ORDER_ID);

        return $orderCompanyAttributes;
    }
}
