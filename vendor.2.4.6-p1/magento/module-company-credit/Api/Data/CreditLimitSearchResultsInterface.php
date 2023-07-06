<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CompanyCredit\Api\Data;

/**
 * Interface for Credit Limit search results.
 *
 * @api
 * @since 100.0.0
 */
interface CreditLimitSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Credit Limit list.
     *
     * @return \Magento\CompanyCredit\Api\Data\CreditDataInterface[]
     */
    public function getItems();

    /**
     * Set Credit Limit list.
     *
     * @param \Magento\CompanyCredit\Api\Data\CreditDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
