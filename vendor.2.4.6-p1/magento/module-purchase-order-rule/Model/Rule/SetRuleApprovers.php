<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Company\Api\RoleManagementInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;

/**
 * Resolver for the purchase order rule creation mutation
 */
class SetRuleApprovers
{
    /**
     * @var RoleManagementInterface
     */
    private RoleManagementInterface $roleManagement;

    /**
     * @param RoleManagementInterface $roleManagement
     */
    public function __construct(
        RoleManagementInterface $roleManagement,
    ) {
        $this->roleManagement = $roleManagement;
    }

    /**
     * Set the approver role IDs required for the rule and whether admin or manager approval is required.
     *
     * @param RuleInterface $rule
     * @param array $roleIds
     */
    public function execute(RuleInterface $rule, array $roleIds)
    {
        $roleIds = $this->setAdminApproval($rule, $roleIds);
        $roleIds = $this->setManagerApproval($rule, $roleIds);
        $rule->setApproverRoleIds($roleIds);
    }

    /**
     * Sets admin approval required for rule
     *
     * @param RuleInterface $rule
     * @param array $roleIds
     * @return array
     */
    private function setAdminApproval(RuleInterface $rule, array $roleIds): array
    {
        $adminIndex = array_search($this->roleManagement->getCompanyAdminRoleId(), $roleIds);
        $rule->setAdminApprovalRequired(false);
        if ($adminIndex !== false) {
            $rule->setAdminApprovalRequired(true);
            unset($roleIds[$adminIndex]);
        }
        return $roleIds;
    }

    /**
     * Sets manager approval required for rule
     *
     * @param RuleInterface $rule
     * @param array $roleIds
     * @return array
     */
    private function setManagerApproval(RuleInterface $rule, array $roleIds): array
    {
        $managerIndex = array_search($this->roleManagement->getCompanyManagerRoleId(), $roleIds);
        $rule->setManagerApprovalRequired(false);
        if ($managerIndex !== false) {
            $rule->setManagerApprovalRequired(true);
            unset($roleIds[$managerIndex]);
        }
        return $roleIds;
    }
}
