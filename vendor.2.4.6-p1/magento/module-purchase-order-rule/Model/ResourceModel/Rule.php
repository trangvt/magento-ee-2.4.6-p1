<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;

/**
 * Rule resource model
 */
class Rule extends AbstractDb
{
    private $ruleApproverTable = 'purchase_order_rule_approver';

    private $appliesToTable = 'purchase_order_rule_applies_to';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('purchase_order_rule', RuleInterface::KEY_ID);
    }

    /**
     * Get the approval role IDs for a specific rule
     *
     * @param int $ruleId
     * @return array
     * @throws LocalizedException
     */
    public function getApproverRoleIds(int $ruleId) : array
    {
        return $this->getRoleRelationshipValues($this->ruleApproverTable, $ruleId);
    }

    /**
     * Save approver role IDs
     *
     * @param int $ruleId
     * @param array $roleIds
     * @return Rule
     */
    public function saveApproverRoleIds(int $ruleId, array $roleIds): Rule
    {
        return $this->saveRoleRelationshipValues($this->ruleApproverTable, $ruleId, $roleIds);
    }

    /**
     * Get the approval role IDs for a specific role
     *
     * @param int $ruleId
     * @return array
     * @throws LocalizedException
     */
    public function getAppliesToRoleIds(int $ruleId) : array
    {
        return $this->getRoleRelationshipValues($this->appliesToTable, $ruleId);
    }

    /**
     * Save the roles which this rule applies to
     *
     * @param int $ruleId
     * @param array $roleIds
     * @return Rule
     */
    public function saveAppliesTo(int $ruleId, array $roleIds): Rule
    {
        return $this->saveRoleRelationshipValues($this->appliesToTable, $ruleId, $roleIds);
    }

    /**
     * Retrieve the relationship values from a specific table
     *
     * @param string $table
     * @param int $ruleId
     * @return array
     * @throws LocalizedException
     */
    private function getRoleRelationshipValues(string $table, int $ruleId) : array
    {
        $connection = $this->getConnection();

        $linkField = RuleInterface::KEY_ID;
        $select = $connection->select()
            ->from(['pora' => $this->getTable($table)], 'role_id')
            ->join(
                ['por' => $this->getMainTable()],
                'por.' . $linkField . ' = pora.' . $linkField,
                []
            )
            ->where('por.' . $linkField . ' = :rule_id' .
                ' AND pora.' . AppliedRuleApproverInterface::KEY_ROLE_ID . ' is not NULL');

        return $connection->fetchCol($select, ['rule_id' => (int) $ruleId]);
    }

    /**
     * Save the relationship values
     *
     * @param string $table
     * @param int $ruleId
     * @param array $roleIds
     * @return $this
     */
    private function saveRoleRelationshipValues(string $table, int $ruleId, array $roleIds)
    {
        $connection = $this->getConnection();
        $table = $this->getTable($table);

        if (!empty($roleIds)) {
            foreach ($roleIds as $roleId) {
                $connection->insertOnDuplicate(
                    $table,
                    ['rule_id' => $ruleId, 'role_id' => $roleId],
                    ['rule_id', 'role_id']
                );
            }

            $connection->delete(
                $table,
                ['rule_id = ?' => $ruleId, 'role_id NOT IN (?)' => $roleIds]
            );
        } else {
            $connection->delete($table, ['rule_id = ?' => $ruleId]);
        }

        return $this;
    }

    /**
     * Return whether the rule requires admin approval
     *
     * @param int $ruleId
     * @return bool
     * @throws LocalizedException
     */
    public function isAdminApprovalRequired(int $ruleId) : bool
    {
        $connection = $this->getConnection();

        $linkField = RuleInterface::KEY_ID;
        $select = $connection->select()
            ->from(['pora' => $this->getTable($this->ruleApproverTable)], 'role_id')
            ->join(
                ['por' => $this->getMainTable()],
                'por.' . $linkField . ' = pora.' . $linkField,
                []
            )
            ->where('por.' . $linkField . ' = :rule_id' .
                ' AND pora.' . RuleInterface::KEY_REQUIRES_ADMIN_APPROVAL . ' = 1');

        return count($connection->fetchAll($select, ['rule_id' => (int) $ruleId])) > 0;
    }

    /**
     * Save whether the rule requires admin approval.
     *
     * @param int $ruleId
     * @param bool $requiresAdminApproval
     * @return Rule
     */
    public function setAdminApprovalRequired(int $ruleId, bool $requiresAdminApproval): Rule
    {
        $connection = $this->getConnection();
        $table = $this->getTable($this->ruleApproverTable);

        if (!$requiresAdminApproval) {
            $connection->delete(
                $table,
                ['rule_id = ?' => $ruleId, 'role_id IS NULL', 'requires_admin_approval = 1']
            );
        } else {
            $connection->insertOnDuplicate(
                $table,
                ['rule_id' => $ruleId, 'requires_admin_approval' => 1],
                ['rule_id', 'requires_admin_approval']
            );
        }

        return $this;
    }

    /**
     * Return whether the rule requires manager approval
     *
     * @param int $ruleId
     * @return bool
     * @throws LocalizedException
     */
    public function isManagerApprovalRequired(int $ruleId) : bool
    {
        $connection = $this->getConnection();

        $linkField = RuleInterface::KEY_ID;
        $select = $connection->select()
            ->from(['pora' => $this->getTable($this->ruleApproverTable)], 'role_id')
            ->join(
                ['por' => $this->getMainTable()],
                'por.' . $linkField . ' = pora.' . $linkField,
                []
            )
            ->where('por.' . $linkField . ' = :rule_id' .
                ' AND pora.' . RuleInterface::KEY_REQUIRES_MANAGER_APPROVAL . ' = 1');

        return count($connection->fetchAll($select, ['rule_id' => (int) $ruleId])) > 0 ? true : false;
    }

    /**
     * Save whether the rule requires manager approval.
     *
     * @param int $ruleId
     * @param bool $requiresManagerApproval
     * @return Rule
     */
    public function setManagerApprovalRequired(int $ruleId, bool $requiresManagerApproval): Rule
    {
        $connection = $this->getConnection();
        $table = $this->getTable($this->ruleApproverTable);

        if (!$requiresManagerApproval) {
            $connection->delete(
                $table,
                ['rule_id = ?' => $ruleId, 'role_id IS NULL', 'requires_manager_approval = 1']
            );
        } else {
            $connection->insertOnDuplicate(
                $table,
                ['rule_id' => $ruleId, 'requires_manager_approval' => 1],
                ['rule_id', 'requires_manager_approval']
            );
        }

        return $this;
    }
}
