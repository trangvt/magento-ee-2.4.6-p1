<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrderRule\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleSearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 * @since 100.2.0
 */
interface RuleRepositoryInterface
{
    /**
     * Save Rule
     *
     * @param RuleInterface $rule
     * @return RuleInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function save(RuleInterface $rule) : RuleInterface;

    /**
     * Retrieve Rule
     *
     * @param string $ruleId
     * @return RuleInterface
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function get($ruleId) : RuleInterface;

    /**
     * Retrieve all active rules for a company
     *
     * @param int $companyId
     * @return RuleSearchResultsInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getByCompanyId(int $companyId);

    /**
     * Retrieve Rule matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return RuleSearchResultsInterface
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Rule
     *
     * @param RuleInterface $rule
     * @return bool true on success
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function delete(RuleInterface $rule) : bool;

    /**
     * Delete Rule by ID
     *
     * @param string $rule
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function deleteById($rule) : bool;

    /**
     * Check if company rule name unique.
     *
     * @param string $ruleName
     * @param int $companyId
     * @param int|null $ruleId
     * @return bool
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function isCompanyRuleNameUnique(string $ruleName, int $companyId, $ruleId = null) : bool;
}
