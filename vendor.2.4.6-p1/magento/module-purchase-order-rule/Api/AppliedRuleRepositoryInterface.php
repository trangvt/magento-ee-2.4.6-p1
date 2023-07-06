<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrderRule\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleSearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 * @since 100.2.0
 */
interface AppliedRuleRepositoryInterface
{
    /**
     * Save applied rule
     *
     * @param AppliedRuleInterface $appliedRule
     * @return AppliedRuleInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function save(AppliedRuleInterface $appliedRule) : AppliedRuleInterface;

    /**
     * Retrieve applied rule by ID
     *
     * @param int $appliedRuleId
     * @return AppliedRuleInterface
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function get(int $appliedRuleId) : AppliedRuleInterface;

    /**
     * Retrieve all applied rules for a purchase order
     *
     * @param int $purchaseOrderId
     * @return AppliedRuleSearchResultsInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getListByPurchaseOrderId(int $purchaseOrderId);

    /**
     * Retrieve applied rules matching a specific criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return AppliedRuleSearchResultsInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete an applied rule
     *
     * @param AppliedRuleInterface $appliedRule
     * @return bool true on success
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function delete(AppliedRuleInterface $appliedRule) : bool;

    /**
     * Delete an applied rule by ID
     *
     * @param int $appliedRule
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function deleteById(int $appliedRule) : bool;
}
