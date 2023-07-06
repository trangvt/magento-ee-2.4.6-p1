<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Filters;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\PurchaseOrderGraphQl\Model\Resolver\SearchCriteriaFilterInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\GetPurchaseOrdersRequireApprovalByCurrentCustomer;

/**
 * Filter for purchase orders which require approval from the current customer
 */
class RequireMyApproval implements SearchCriteriaFilterInterface
{
    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var GetPurchaseOrdersRequireApprovalByCurrentCustomer
     */
    private GetPurchaseOrdersRequireApprovalByCurrentCustomer $getPurchaseOrdersRequireApprovalByCurrentCustomer;

    /**
     * @param GetPurchaseOrdersRequireApprovalByCurrentCustomer $getPurchaseOrdersRequireApprovalByCurrentCustomer
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        GetPurchaseOrdersRequireApprovalByCurrentCustomer $getPurchaseOrdersRequireApprovalByCurrentCustomer,
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->getPurchaseOrdersRequireApprovalByCurrentCustomer = $getPurchaseOrdersRequireApprovalByCurrentCustomer;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
    }

    /**
     * @inheritdoc
     */
    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder, $value): SearchCriteriaBuilder
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        return $searchCriteriaBuilder->addFilter(
            'entity_id',
            $this->getPurchaseOrdersRequireApprovalByCurrentCustomer->execute()->getAllIds(),
            'in'
        );
    }
}
