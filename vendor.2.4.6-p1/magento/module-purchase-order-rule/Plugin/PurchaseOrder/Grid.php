<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Plugin\PurchaseOrder;

use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\UserRoleManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\Grid as PurchaseOrderGrid;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover;

/**
 * Plugin to restrict view for purchase order rule that require approval
 */
class Grid
{
    /**
     * @var AppliedRuleApprover
     */
    private $appliedRuleApprover;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var UserRoleManagement
     */
    private $userRoleManagement;

    /**
     * @param AppliedRuleApprover $appliedRuleApprover
     * @param CompanyContext $companyContext
     * @param CompanyAdminPermission $companyAdminPermission
     * @param UserRoleManagement $userRoleManagement
     */
    public function __construct(
        AppliedRuleApprover $appliedRuleApprover,
        CompanyContext $companyContext,
        CompanyAdminPermission $companyAdminPermission,
        UserRoleManagement $userRoleManagement
    ) {
        $this->appliedRuleApprover = $appliedRuleApprover;
        $this->companyContext = $companyContext;
        $this->companyAdminPermission = $companyAdminPermission;
        $this->userRoleManagement = $userRoleManagement;
    }

    /**
     * After PurchaseOrderGrid::isAllowed
     *
     * @param PurchaseOrderGrid $subject
     * @param bool $result
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterIsAllowed(PurchaseOrderGrid $subject, bool $result)
    {
        if ($subject->getData('approvalGrid')) {
            return $this->isApprovalGridAllowed();
        }

        return $result;
    }

    /**
     * Check if Approval grid is allowed for current customer.
     *
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isApprovalGridAllowed()
    {
        $customerId = $this->companyContext->getCustomerId();
        if ($this->companyAdminPermission->isGivenUserCompanyAdmin($customerId)) {
            return (bool) count($this->appliedRuleApprover->getPurchaseOrderIdsByAppliedRole(
                AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN
            ));
        }

        $purchaseOrderIds = $this->appliedRuleApprover->getPurchaseOrderIdsByAppliedRole(
            AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER
        );

        if (count($purchaseOrderIds)) {
            return true;
        }
        $roles = $this->userRoleManagement->getRolesByUserId($customerId);
        $role = current($roles);
        $purchaseOrderIds = $this->appliedRuleApprover->getPurchaseOrderIdsByAppliedRole(
            AppliedRuleApproverInterface::APPROVER_TYPE_ROLE,
            $role->getId()
        );
        return (bool) count($purchaseOrderIds);
    }
}
