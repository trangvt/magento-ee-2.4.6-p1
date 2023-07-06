<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\OrderHistorySearch\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Block class for the additional "created by" column in the order history search results.
 *
 * @api
 * @since 100.2.0
 */
class CreatedBy extends Template
{
    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerNameGenerationInterface
     */
    private $customerNameGenerator;

    /**
     * @param Template\Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerNameGenerationInterface $customerNameGenerator
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CustomerRepositoryInterface $customerRepository,
        CustomerNameGenerationInterface $customerNameGenerator,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerRepository = $customerRepository;
        $this->customerNameGenerator = $customerNameGenerator;
    }

    /**
     * Set order.
     *
     * @param OrderInterface $order
     * @return $this
     * @since 100.2.0
     */
    public function setOrder(OrderInterface $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Get order.
     *
     * @return OrderInterface
     */
    private function getOrder()
    {
        return $this->order;
    }

    /**
     * Get customer name.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @since 100.2.0
     */
    public function getCreatedBy()
    {
        $customerName = '';
        $customerId = $this->getOrder()->getCustomerId();

        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $customerName = $this->customerNameGenerator->getCustomerName($customer);
        }

        return $customerName;
    }
}
