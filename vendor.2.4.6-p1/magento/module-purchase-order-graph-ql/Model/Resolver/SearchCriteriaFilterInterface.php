<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Interface which defines methods to be implemented by filters for PurchaseOrdersSearchCriteria
 */
interface SearchCriteriaFilterInterface
{
    /**
     * Apply filter to the passed $searchCriteriaBuilder
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param mixed $value
     * @return SearchCriteriaBuilder
     */
    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder, $value): SearchCriteriaBuilder;
}
