<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Rule search results interface
 *
 * @api
 */
interface RuleSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get rule list
     *
     * @return RuleInterface[]
     */
    public function getItems();

    /**
     * Set rule list
     *
     * @param RuleInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
