<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule\Validate;

use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;

class ValidateRuleBeforeSave
{
    /**
     * @var RoleRepositoryInterface
     */
    private RoleRepositoryInterface $roleRepository;

    /**
     * @var CompanyUser
     */
    private CompanyUser $companyUser;

    /**
     * @var RuleConditionPool
     */
    private RuleConditionPool $ruleConditionPool;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var RoleManagementInterface
     */
    private RoleManagementInterface $roleManagement;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @param RoleRepositoryInterface $roleRepository
     * @param CompanyUser $companyUser
     * @param RuleConditionPool $ruleConditionPool
     * @param RuleRepositoryInterface $ruleRepository
     * @param RoleManagementInterface $roleManagement
     * @param Json $serializer
     */
    public function __construct(
        RoleRepositoryInterface $roleRepository,
        CompanyUser $companyUser,
        RuleConditionPool $ruleConditionPool,
        RuleRepositoryInterface $ruleRepository,
        RoleManagementInterface $roleManagement,
        Json $serializer
    ) {
        $this->roleRepository = $roleRepository;
        $this->companyUser = $companyUser;
        $this->ruleConditionPool = $ruleConditionPool;
        $this->ruleRepository = $ruleRepository;
        $this->roleManagement = $roleManagement;
        $this->serializer = $serializer;
    }

    /**
     * Validate the incoming request is valid for a Purchase Order rule
     *
     * @param RuleInterface $rule
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(RuleInterface $rule)
    {
        if (!$rule->getName() || trim($rule->getName()) === '') {
            throw new LocalizedException(__('The approval rule must have a name.'));
        }

        $this->validateConditions($rule);

        if (empty($rule->getApproverRoleIds())
            && !$rule->isAdminApprovalRequired()
            && !$rule->isManagerApprovalRequired()
        ) {
            throw new LocalizedException(__('At least one approver is required to configure this rule.'));
        }

        if (!$rule->isAppliesToAll() && empty($rule->getAppliesToRoleIds())) {
            throw new LocalizedException(__('This rule must apply to at least one or all roles.'));
        }

        $this->validateRoles($rule->getAppliesToRoleIds());
        $this->validateRoles($rule->getApproverRoleIds());
        $this->validateCompany($rule);
    }

    /**
     * Ensure rule belongs to company and the rule name is unique
     *
     * @param RuleInterface $rule
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateCompany(RuleInterface $rule): void
    {
        $companyId = (int)$this->companyUser->getCurrentCompanyId();

        if ($rule->getId() && ($rule->getCompanyId() !== $companyId)) {
            throw new LocalizedException(
                __('The approval rule with ID "%ruleId" does not exist.', ['ruleId' => $rule->getId()])
            );
        }

        if (!$this->ruleRepository->isCompanyRuleNameUnique($rule->getName(), $companyId, $rule->getId())) {
            throw new LocalizedException(__('This rule name already exists. Enter a unique rule name.'));
        }
    }

    /**
     * Validate the request conditions
     *
     * @param RuleInterface $rule
     * @return void
     * @throws LocalizedException
     */
    private function validateConditions(RuleInterface $rule)
    {
        $conditionsData = $this->serializer->unserialize($rule->getConditionsSerialized());

        if (!isset($conditionsData['conditions'])) {
            throw new LocalizedException(__('Required field is not complete.'));
        }

        $ruleConditions = $conditionsData['conditions'];

        if (!is_array($ruleConditions) || empty($ruleConditions)) {
            throw new LocalizedException(__('Required field is not complete.'));
        }
        // Iterate through conditions and ensure all required data is present
        foreach ($ruleConditions as $condition) {
            if (!is_array($condition)) {
                throw new LocalizedException(__('Required data is missing from a rule condition.'));
            }
            $this->validateCodition($condition);
        }
    }

    /**
     * Validate condition data
     *
     * @param array $condition
     * @return void
     * @throws LocalizedException
     */
    private function validateCodition(array $condition): void
    {
        if (!isset($condition['attribute']) || !isset($condition['operator']) || !isset($condition['value'])) {
            throw new LocalizedException(__('Required data is missing from a rule condition.'));
        }

        // Hand validation of rule condition to validator class as configured in DI for pool
        $this->ruleConditionPool->validateRuleCondition(
            $condition['attribute'],
            $condition['operator'],
            (string) $condition['value']
        );
    }

    /**
     * Validate that all role selections are valid
     *
     * @param array $roleIds
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateRoles(array $roleIds)
    {
        foreach ($roleIds as $roleId) {
            $this->validateExistingCompanyRoleId((int) $roleId);
        }
    }

    /**
     * Ensure roleId is present and role belongs to customer company
     *
     * @param int $roleId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateExistingCompanyRoleId(int $roleId): void
    {
        if ($roleId == $this->roleManagement->getCompanyAdminRoleId() ||
            $roleId == $this->roleManagement->getCompanyManagerRoleId()) {
            return;
        }

        try {
            $companyRole = $this->roleRepository->get($roleId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(
                __('The company role with ID "%roleId" does not exist.', ['roleId' => $roleId])
            );
        }

        // If the role is not part of the users current company we throw a generic does not exist error
        if ($this->companyUser->getCurrentCompanyId() !== $companyRole->getCompanyId()) {
            throw new LocalizedException(
                __('The company role with ID "%roleId" does not exist.', ['roleId' => $roleId])
            );
        }
    }
}
