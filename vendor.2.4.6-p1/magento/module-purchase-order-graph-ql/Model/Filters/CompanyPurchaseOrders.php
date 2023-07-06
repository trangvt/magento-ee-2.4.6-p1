<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Filters;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\PurchaseOrderGraphQl\Model\Resolver\SearchCriteriaFilterInterface;

/**
 * Filter for purchase orders of a company
 */
class CompanyPurchaseOrders implements SearchCriteriaFilterInterface
{
    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $context;

    /**
     * @param CompanyManagementInterface $companyManagement
     * @param ResolverAccess $resolverAccess
     * @param UserContextInterface $context
     * @param array $allowedResources
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        ResolverAccess $resolverAccess,
        UserContextInterface $context,
        array $allowedResources = []
    ) {
        $this->companyManagement = $companyManagement;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder, $value): SearchCriteriaBuilder
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        return $searchCriteriaBuilder->addFilter(
            'company_id',
            $this->companyManagement->getByCustomerId($this->context->getUserId())->getId()
        );
    }
}
