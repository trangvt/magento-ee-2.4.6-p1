<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel;

use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\UserRoleManagement;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\PurchaseOrder\Grid\Collection;
use Magento\PurchaseOrderRule\Model\ResourceModel\PurchaseOrder\Grid\CollectionFactory;

/**
 * Get purchase orders which require approval by the current customer
 */
class GetPurchaseOrdersRequireApprovalByCurrentCustomer
{
    /**
     * @var CompanyContext
     */
    private CompanyContext $companyContext;

    /**
     * @var CompanyUser
     */
    private CompanyUser $companyUser;

    /**
     * @var UserRoleManagement
     */
    private UserRoleManagement $userRoleManagement;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $purchaseOrdersCollectionFactory;

    /**
     * @var CompanyAdminPermission
     */
    private CompanyAdminPermission $companyAdminPermission;

    /**
     * @var getPurchaseOrderIdsByAppliedRole
     */
    private GetPurchaseOrderIdsByAppliedRole $getPurchaseOrderIdsByAppliedRole;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CompanyContext $companyContext
     * @param CompanyUser $companyUser
     * @param UserRoleManagement $userRoleManagement
     * @param CompanyAdminPermission $companyAdminPermission
     * @param CollectionFactory $purchaseOrdersCollectionFactory
     * @param GetPurchaseOrderIdsByAppliedRole $getPurchaseOrderIdsByAppliedRole
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CompanyContext $companyContext,
        CompanyUser $companyUser,
        UserRoleManagement $userRoleManagement,
        CompanyAdminPermission $companyAdminPermission,
        CollectionFactory $purchaseOrdersCollectionFactory,
        GetPurchaseOrderIdsByAppliedRole $getPurchaseOrderIdsByAppliedRole,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->companyContext = $companyContext;
        $this->companyUser = $companyUser;
        $this->userRoleManagement = $userRoleManagement;
        $this->purchaseOrdersCollectionFactory = $purchaseOrdersCollectionFactory;
        $this->companyAdminPermission = $companyAdminPermission;
        $this->getPurchaseOrderIdsByAppliedRole = $getPurchaseOrderIdsByAppliedRole;
    }

    /**
     * Get purchase orders that require approval by current customer.
     *
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): Collection
    {
        $purchaseOrderIds = $this->getPurchaseOrderIdsByCurrentCustomer();
        $customerId = $this->companyContext->getCustomerId();
        $companyId = $this->companyUser->getCurrentCompanyId();
        $connection = $this->resourceConnection->getConnection();
        $sql = $connection->select()
            ->from(
                ['main_table' => $connection->getTableName('purchase_order_applied_rule')],
                ['purchase_order_id']
            )->join(
                ['poara' => $connection->getTableName('purchase_order_applied_rule_approver')],
                'main_table.applied_rule_id = poara.applied_rule_id'
            )->where('purchase_order_id IN (?)', $purchaseOrderIds)
            ->where('poara.status != ?', AppliedRuleApproverInterface::STATUS_PENDING)
            ->where('customer_id = ?', $customerId);

        $purchaseOrdersIdsProcessedByCurrentCustomer = [];
        foreach ($connection->fetchAll($sql) as $row) {
            $purchaseOrdersIdsProcessedByCurrentCustomer[] = $row['purchase_order_id'];
        }

        $purchaseOrdersCollection = $this->purchaseOrdersCollectionFactory->create();

        return $purchaseOrdersCollection
            ->addFieldToFilter('main_table.status', PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED)
            ->addFieldToFilter('main_table.company_id', $companyId)
            ->addFieldToFilter(
                'main_table.entity_id',
                ['in' => array_diff($purchaseOrderIds, $purchaseOrdersIdsProcessedByCurrentCustomer)]
            );
    }

    /**
     * Get purchase order ids by current customer.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getPurchaseOrderIdsByCurrentCustomer(): array
    {
        $customerId = $this->companyContext->getCustomerId();
        if ($this->companyAdminPermission->isCurrentUserCompanyAdmin()) {
            $purchaseOrderIds = $this->getPurchaseOrderIdsByAppliedRole->execute(
                AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN
            );
        } else {
            $roles = $this->userRoleManagement->getRolesByUserId($customerId);
            $role = current($roles);

            $purchaseOrderIds = $this->getPurchaseOrderIdsByAppliedRole->execute(
                AppliedRuleApproverInterface::APPROVER_TYPE_ROLE,
                (int)$role->getId()
            );

            if ($this->getPurchaseOrderIdsByAppliedRole->hasCustomerSubordinates($customerId)) {
                $managerPurchaseOrderIds = $this->getPurchaseOrderIdsByAppliedRole->execute(
                    AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER
                );
                $purchaseOrderIds = array_merge($managerPurchaseOrderIds, $purchaseOrderIds);
            }
        }

        return array_unique($purchaseOrderIds);
    }
}
