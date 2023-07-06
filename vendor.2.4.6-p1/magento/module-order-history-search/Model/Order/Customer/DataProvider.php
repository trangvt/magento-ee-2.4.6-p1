<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Order\Customer;

use Magento\Company\Api\AuthorizationInterface as CompanyAuthorization;
use Magento\Company\Api\StatusServiceInterface as CompanyConfig;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection  as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory  as CustomerCollectionFactory;
use Magento\Customer\Model\Session;

/**
 * Class DataProvider.
 *
 * Options data provider for order history customers.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class DataProvider
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CompanyConfig
     */
    private $companyConfig;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @var CompanyAuthorization
     */
    private $companyAuthorization;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @param Session $session
     * @param CompanyConfig $companyConfig
     * @param CompanyStructure $companyStructure
     * @param CompanyAuthorization $companyAuthorization
     * @param CustomerCollectionFactory $customerCollectionFactory
     */
    public function __construct(
        Session $session,
        CompanyConfig $companyConfig,
        CompanyStructure $companyStructure,
        CompanyAuthorization $companyAuthorization,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->session = $session;
        $this->companyConfig = $companyConfig;
        $this->companyStructure = $companyStructure;
        $this->companyAuthorization = $companyAuthorization;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Gets all customers visible to the current customer as an options array.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllowedCustomerOptions(): array
    {
        $currentCustomerId = $this->session->getCustomerId();
        $allowedCustomerIds = [$currentCustomerId];

        if ($this->companyConfig->isActive()
            && $this->companyAuthorization->isAllowed('Magento_Sales::view_orders_sub')
        ) {
            $childCustomerIds = $this->companyStructure->getAllowedChildrenIds($currentCustomerId);
            $allowedCustomerIds = array_merge($allowedCustomerIds, $childCustomerIds);
        }

        /** @var CustomerCollection $allowedCustomerCollection */
        $allowedCustomerCollection = $this->customerCollectionFactory->create();
        $allowedCustomerCollection->addFieldToFilter('entity_id', $allowedCustomerIds)
            ->addAttributeToSort('firstname')
            ->addAttributeToSort('lastname');

        return $this->buildOptionsArray($allowedCustomerCollection);
    }

    /**
     * Build the options array for the supplied customer collection.
     *
     * @param CustomerCollection $customerCollection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function buildOptionsArray(CustomerCollection $customerCollection): array
    {
        $optionsArray = [];

        /** @var Customer $customer */
        foreach ($customerCollection->getItems() as $customer) {
            $optionsArray[] = [
                'value' => $customer->getId(),
                'label' => $customer->getName()
            ];
        }

        return $optionsArray;
    }
}
