<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;

/**
 * Get an array of PO IDs by applied role
 */
class GetPurchaseOrderIdsByAppliedRole
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var CompanyStructure
     */
    private CompanyStructure $companyStructure;

    /**
     * @var CompanyContext
     */
    private CompanyContext $companyContext;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CompanyStructure $companyStructure
     * @param CompanyContext $companyContext
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CompanyStructure $companyStructure,
        CompanyContext $companyContext
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->companyStructure = $companyStructure;
        $this->companyContext = $companyContext;
    }

    /**
     * Get purchase order ids with requires approval form specific role.
     *
     * @param mixed $roleType
     * @param mixed|null $roleId
     * @return array
     * @throws LocalizedException
     */
    public function execute($roleType, $roleId = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $bind = ['role_type' => $roleType];
        $select = $connection->select()
            ->from(
                ['poara' => $this->resourceConnection->getTableName('purchase_order_applied_rule_approver')],
                ['po.entity_id']
            )
            ->joinLeft(
                ['poar' => $this->resourceConnection->getTableName('purchase_order_applied_rule')],
                'poara.' . AppliedRuleApproverInterface::KEY_APPLIED_RULE_ID .
                ' = poar.' . AppliedRuleInterface::KEY_ID,
                []
            )->joinLeft(
                ['po' => $this->resourceConnection->getTableName('purchase_order')],
                'poar.' . AppliedRuleInterface::KEY_PURCHASE_ORDER_ID . ' = po.' . PurchaseOrderInterface::ENTITY_ID
            );

        $whereCondition = 'poara.' . AppliedRuleApproverInterface::KEY_APPROVER_TYPE . ' = :role_type';
        if ($roleType === AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN) {
            $whereCondition = $this->restrictToCompanyAdmin($whereCondition, $bind);
        } elseif ($roleType === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
            $whereCondition = $this->restrictToManager($whereCondition);
        } else {
            // Filter purchase orders that require approval from specific role
            $bind['role_id'] = $roleId;
            $whereCondition .= ' AND poara.' . AppliedRuleApproverInterface::KEY_ROLE_ID . ' = :role_id';
        }

        $select->where($whereCondition);
        return $connection->fetchCol($select, $bind);
    }

    /**
     * Restrict query to company admin
     *
     * @param string $whereCondition
     * @param array $bind
     * @return string
     * @throws LocalizedException
     */
    private function restrictToCompanyAdmin(string $whereCondition, array &$bind) : string
    {
        $bind['manager_role_type'] = AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER;
        // Filter purchase orders that require approval from admin user
        $whereCondition .= ' AND poara.' . AppliedRuleApproverInterface::KEY_ROLE_ID . ' IS NULL)' .
            ' OR (poara.' . AppliedRuleApproverInterface::KEY_APPROVER_TYPE . ' = :manager_role_type';

        // The admin could also be a manager, so also pull any manager approval POs
        return $this->restrictToManager($whereCondition);
    }

    /**
     * Restrict query to the manager
     *
     * @param string $whereCondition
     * @return string
     * @throws LocalizedException
     */
    private function restrictToManager(string $whereCondition) : string
    {
        // Filter subordinates purchase orders that require approval from manager
        $subordinatesIds = $this->companyStructure->getAllowedChildrenIds($this->companyContext->getCustomerId());
        if (count($subordinatesIds) > 0) {
            $whereCondition .= ' AND poara.' . AppliedRuleApproverInterface::KEY_ROLE_ID . ' IS NULL' .
                ' AND po.' . PurchaseOrderInterface::CREATOR_ID . ' in (' . join(',', $subordinatesIds) . ')';
        }

        return $whereCondition;
    }

    /**
     * Check if user is manager.
     *
     * @param int $customerId
     * @return bool
     */
    public function hasCustomerSubordinates(int $customerId): bool
    {
        return (bool) count($this->companyStructure->getAllowedChildrenIds($customerId));
    }
}
