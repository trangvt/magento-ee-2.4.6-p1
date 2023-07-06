<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

/**
 * Class OrderAddressProvider.
 *
 * Provider of addresses for order. Used to fetch addresses for orders created by specified customer.
 */
class OrderAddressProvider
{
    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Api\Data\OrderAddressInterface[]
     */
    private $customerOrderAddresses = [];

    /**
     * OrderAddressProvider constructor.
     *
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CollectionFactoryInterface $orderCollectionFactory,
        OrderAddressRepositoryInterface $orderAddressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Returns addresses collection for customer ID
     *
     * @param int $customerId
     *
     * @return OrderAddressInterface[]
     */
    public function getByCustomerId(int $customerId): array
    {
        if (!isset($this->customerOrderAddresses[$customerId])) {
            $orders = $this->orderCollectionFactory->create($customerId)->addFieldToSelect(OrderInterface::ENTITY_ID);
            $addressSearchCriteria = $this->searchCriteriaBuilder
                ->addFilter(OrderAddressInterface::ADDRESS_TYPE, Address::TYPE_SHIPPING)
                ->addFilter(OrderAddressInterface::PARENT_ID, $orders->getAllIds(), 'in');
            $addresses = $this->orderAddressRepository->getList($addressSearchCriteria->create());

            $this->customerOrderAddresses[$customerId] = $addresses->getItems();
        }

        return $this->customerOrderAddresses[$customerId];
    }
}
