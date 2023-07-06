<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Filters;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\PurchaseOrderGraphQl\Model\Resolver\SearchCriteriaFilterInterface;

/**
 * Filter for purchase orders status
 */
class Status implements SearchCriteriaFilterInterface
{
    /**
     * @inheritdoc
     */
    public function apply(SearchCriteriaBuilder $searchCriteriaBuilder, $value): SearchCriteriaBuilder
    {
        return $searchCriteriaBuilder->addFilter('status', $value);
    }
}
