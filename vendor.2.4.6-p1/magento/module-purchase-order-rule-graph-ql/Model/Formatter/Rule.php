<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\Formatter;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRuleGraphQl\Model\GetRoleData;

/**
 * Formatter for the purchase order rule
 */
class Rule
{
    private const CONDITION_OPERATOR = [
        '>' => 'MORE_THAN',
        '<' => 'LESS_THAN',
        '>=' => 'MORE_THAN_OR_EQUAL_TO',
        '<=' => 'LESS_THAN_OR_EQUAL_TO'
    ];

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private RoleRepositoryInterface $roleRepository;

    /**
     * @var RoleManagementInterface
     */
    private RoleManagementInterface $roleManagement;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @var GetRoleData
     */
    private GetRoleData $getRoleData;

    /**
     * @var Uid
     */
    private Uid $uid;

    /**
     * @var array
     */
    private array $companyRoles = [];

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param RoleRepositoryInterface $roleRepository
     * @param RoleManagementInterface $roleManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Json $serializer
     * @param GetRoleData $getRoleData
     * @param Uid $uid
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RoleRepositoryInterface $roleRepository,
        RoleManagementInterface $roleManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Json $serializer,
        GetRoleData $getRoleData,
        Uid $uid
    ) {
        $this->customerRepository = $customerRepository;
        $this->roleRepository = $roleRepository;
        $this->roleManagement = $roleManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        $this->getRoleData = $getRoleData;
        $this->uid = $uid;
    }

    /**
     * Retrieve formatted rule data
     *
     * @param RuleInterface $rule
     * @return array
     */
    public function getRuleData(RuleInterface $rule): array
    {
        return [
            'uid' => $this->uid->encode((string)$rule->getId()),
            'name' => $rule->getName(),
            'description' => $rule->getDescription(),
            'status' => $rule->isActive() ? 'ENABLED' : 'DISABLED',
            'created_at' => $rule->getCreatedAt(),
            'updated_at' => $rule->getUpdatedAt(),
            'created_by' => $this->getCustomerName($rule->getCreatedBy()),
            'applies_to_roles' => array_map(
                function (RoleInterface $role) {
                    return $this->getRoleData->execute($role);
                },
                $this->getAppliesToRoles($rule)
            ),
            'approver_roles' => array_map(
                function (RoleInterface $role) {
                    return $this->getRoleData->execute($role);
                },
                $this->getApproverRoles($rule)
            ),
            'condition' => $this->getRuleCondition($rule)
        ];
    }

    /**
     * Retrieve rule condition
     *
     * @param RuleInterface $rule
     * @return array|null
     */
    private function getRuleCondition(RuleInterface $rule): ?array
    {
        $conditions = $this->serializer->unserialize($rule->getConditionsSerialized());

        if (!isset($conditions['conditions'][0])) {
            return null;
        }

        $condition = $conditions['conditions'][0];

        $operator = isset($condition['operator'])
            ? self::CONDITION_OPERATOR[$condition['operator']] ?? null
            : null;
        $attribute = isset($condition['attribute'])
            ? strtoupper($condition['attribute'])
            : null;
        $value = $condition['value'] ?? null;

        if (isset($condition['currency_code'])) {
            return [
                'operator' => $operator,
                'amount' => [
                    'value' => $value,
                    'currency' => $condition['currency_code']
                ],
                'attribute' => $attribute
            ];
        }

        return [
            'operator' => $operator,
            'quantity' => $value,
            'attribute' => $attribute
        ];
    }

    /**
     * Retrieve array of roles the rule applies to
     *
     * @param RuleInterface $rule
     * @return RoleInterface[]
     */
    private function getAppliesToRoles(RuleInterface $rule): array
    {
        return $rule->isAppliesToAll() ? [] : $this->getRoles($rule->getCompanyId(), $rule->getAppliesToRoleIds());
    }

    /**
     * Retrieve customer name
     *
     * @param int $customerId
     * @return string
     */
    private function getCustomerName(int $customerId): string
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (LocalizedException $exception) {
            return 'UNKNOWN';
        }

        return $customer->getFirstname() . ' ' . $customer->getLastname();
    }

    /**
     * Retrieve roles that can approve the rule
     *
     * @param RuleInterface $rule
     * @return RoleInterface[]
     */
    private function getApproverRoles(RuleInterface $rule): array
    {
        $roles = $this->getRoles($rule->getCompanyId(), $rule->getApproverRoleIds());
        if ($rule->isAdminApprovalRequired()) {
            array_unshift($roles, $this->roleManagement->getAdminRole());
        }
        if ($rule->isManagerApprovalRequired()) {
            array_unshift($roles, $this->roleManagement->getManagerRole());
        }
        return $roles;
    }

    /**
     * Retrieve roles based on ids
     *
     * @param int $companyId
     * @param array $ids
     * @return RoleInterface[]
     */
    private function getRoles(int $companyId, array $ids): array
    {
        $roles = [];
        $companyRoles = $this->getCompanyRoles($companyId);

        foreach ($ids as $id) {
            if (!isset($companyRoles[$id])) {
                continue;
            }
            $roles[] = $companyRoles[$id];
        }

        return $roles;
    }

    /**
     * Retrieve company roles
     *
     * @param int $companyId
     * @return array
     */
    private function getCompanyRoles(int $companyId): array
    {
        if (!isset($this->companyRoles[$companyId])) {
            try {
                $roles = $this->roleRepository->getList(
                    $this->searchCriteriaBuilder->addFilter('company_id', $companyId)->create()
                )->getItems();

                foreach ($roles as $role) {
                    $this->companyRoles[$companyId][$role->getId()] = $role;
                }
            } catch (LocalizedException $exception) {
                $this->companyRoles[$companyId] = [];
            }
        }
        return $this->companyRoles[$companyId];
    }
}
