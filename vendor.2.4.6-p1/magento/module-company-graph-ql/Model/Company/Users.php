<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\ResourceModel\Users\Grid\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Company user data provider
 */
class Users
{
    /**
     * Status of company user - active
     */
    public const STATUS_ACTIVE = 'ACTIVE';

    /**
     * Status of company user - inactive
     */
    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * @var CollectionFactory
     */
    private $companyUserCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var array
     */
    private $companyUserStatus;

    /**
     * @param CollectionFactory $companyUserCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteria
     * @param FilterBuilder $filterBuilder
     * @param array $companyUserStatus
     */
    public function __construct(
        CollectionFactory $companyUserCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteria,
        FilterBuilder $filterBuilder,
        array $companyUserStatus = []
    ) {
        $this->companyUserCollectionFactory = $companyUserCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->searchCriteria = $searchCriteria;
        $this->filterBuilder = $filterBuilder;
        $this->companyUserStatus = $companyUserStatus;
    }

    /**
     * Get company users
     *
     * @param CompanyInterface $company
     * @param array $args
     * @return CustomerSearchResultsInterface
     */
    public function getCompanyUsers(CompanyInterface $company, array $args): CustomerSearchResultsInterface
    {
        $usersCollection = $this->companyUserCollectionFactory->create();
        if (isset($args['filter']['status'])) {
            $usersCollection->addFieldToFilter(
                'company_customer.' . CompanyCustomerInterface::STATUS,
                array_search(
                    $args['filter']['status'],
                    array_column($this->companyUserStatus, 'label', 'value'),
                    false
                )
            );
        }

        $usersCollection->addFieldToFilter(
            'company.' . CompanyInterface::COMPANY_ID,
            $company->getId()
        );

        $companyUserIds = $usersCollection->getAllIds();
        $filters = [];
        $filters[] = $this->filterBuilder
            ->setField('entity_id')
            ->setConditionType('in')
            ->setValue($companyUserIds)
            ->create();

        $this->searchCriteria
            ->addFilters($filters)
            ->setCurrentPage($args['currentPage'])
            ->setPageSize($args['pageSize']);

        $searchCriteria = $this->searchCriteria->create();

        return $this->customerRepository->getList($searchCriteria);
    }

    /**
     * Get company user's status
     *
     * @return string[]
     */
    public function getCompanyUserStatus(): array
    {
        return $this->companyUserStatus;
    }
}
