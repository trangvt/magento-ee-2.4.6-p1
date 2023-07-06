<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Creates a SearchCriteria object from the provided arguments
 */
class GetPurchaseOrdersSearchCriteria
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    /**
     * @var SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;

    /**
     * @var SearchCriteriaFilterInterface[] $filters
     */
    private array $filters;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SortOrderBuilder $sortOrderBuilder
     * @param array $filters
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SortOrderBuilder $sortOrderBuilder,
        array $filters = []
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->filters = $filters;
    }

    /**
     * Returns a SearchCriteria object created from the passed args
     *
     * @param array $filterArgs
     * @param int $currentPage
     * @param int $pageSize
     * @param int $customerId
     * @return SearchCriteria
     */
    public function execute(
        array $filterArgs,
        int $currentPage,
        int $pageSize,
        int $customerId
    ): SearchCriteria {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        foreach ($filterArgs as $key => $value) {
            if (!isset($this->filters[$key])) {
                continue;
            }
            $searchCriteriaBuilder = $this->filters[$key]->apply($searchCriteriaBuilder, $value);
        }

        if ((!isset($filterArgs['require_my_approval']) || $filterArgs['require_my_approval'] === false) &&
            (!isset($filterArgs['company_purchase_orders']) || $filterArgs['company_purchase_orders'] === false)
        ) {
            $searchCriteriaBuilder->addFilter(PurchaseOrderInterface::CREATOR_ID, $customerId);
        }

        $sortOrder = $this->sortOrderBuilder->setField(PurchaseOrderInterface::ENTITY_ID)
            ->setDescendingDirection()
            ->create();

        return $searchCriteriaBuilder
            ->setCurrentPage($currentPage)
            ->setPageSize($pageSize)
            ->addSortOrder($sortOrder)
            ->create();
    }
}
