<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\PurchaseOrderRule\Model\ResourceModel\Rule\Collection;

class RuleAppliesToFilter implements CustomFilterInterface
{
    /**
     * Apply applies_to_role_ids filter to collection
     *
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool Whether the filter is applied
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $value = $filter->getValue();
        if (!is_array($value) && is_string($value) && strpos($value, ',') === false) {
            $value = [$value];
        } elseif (is_string($value) && strpos($value, ',') !== false) {
            $value = explode(',', $value);
        }

        /** @var Collection $collection */
        $collection->addAppliesToFilter($value);

        return true;
    }
}
