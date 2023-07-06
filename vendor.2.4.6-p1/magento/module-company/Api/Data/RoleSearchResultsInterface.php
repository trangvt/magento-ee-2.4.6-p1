<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for role search results.
 *
 * @api
 * @since 100.0.0
 */
interface RoleSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get roles list.
     *
     * @return \Magento\Company\Api\Data\RoleInterface[]
     */
    public function getItems();

    /**
     * Set roles list.
     *
     * @param \Magento\Company\Api\Data\RoleInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
