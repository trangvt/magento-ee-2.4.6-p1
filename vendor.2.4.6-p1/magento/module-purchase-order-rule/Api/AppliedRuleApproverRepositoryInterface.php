<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrderRule\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverSearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 * @since 100.2.0
 */
interface AppliedRuleApproverRepositoryInterface
{
    /**
     * Save applied rule
     *
     * @param AppliedRuleApproverInterface $appliedRuleApprover
     * @return AppliedRuleApproverInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function save(AppliedRuleApproverInterface $appliedRuleApprover) : AppliedRuleApproverInterface;

    /**
     * Retrieve applied rule by ID
     *
     * @param int $appliedRuleApproverId
     * @return AppliedRuleApproverInterface
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function get(int $appliedRuleApproverId) : AppliedRuleApproverInterface;

    /**
     * Retrieve applied rules matching a specific criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return AppliedRuleApproverSearchResultsInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Retrieve all approvers from an applied rule
     *
     * @param int $appliedRuleId
     * @return AppliedRuleApproverSearchResultsInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getListByAppliedRuleId(int $appliedRuleId);

    /**
     * Delete an applied rule
     *
     * @param AppliedRuleApproverInterface $appliedRuleApprover
     * @return bool true on success
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function delete(AppliedRuleApproverInterface $appliedRuleApprover) : bool;

    /**
     * Delete an applied rule by ID
     *
     * @param int $appliedRuleApprover
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function deleteById(int $appliedRuleApprover) : bool;
}
