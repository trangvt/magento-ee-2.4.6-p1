<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Plugin\Customer\Authorization;

use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\UserRole;
use Magento\Company\Model\UserRoleManagement;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization\ViewPurchaseOrder;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;

/**
 * Plugin to restrict view to remaining rule approvers and company admin
 */
class ViewPurchaseOrderPlugin
{
    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * @var AppliedRuleRepositoryInterface
     */
    private $appliedRuleRepository;

    /**
     * @var UserRoleManagement
     */
    private $userRoleManagement;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @param CompanyAdminPermission $companyAdminPermission
     * @param CompanyContext $companyContext
     * @param Structure $companyStructure
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param UserRoleManagement $userRoleManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CompanyAdminPermission $companyAdminPermission,
        CompanyContext $companyContext,
        Structure $companyStructure,
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        UserRoleManagement $userRoleManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->companyAdminPermission = $companyAdminPermission;
        $this->companyContext = $companyContext;
        $this->companyStructure = $companyStructure;
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->userRoleManagement = $userRoleManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * After ViewPurchaseOrder::isAllowed; further restrict to company admin and remaining rule approvers
     *
     * @param ViewPurchaseOrder $subject
     * @param bool $result
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsAllowed(ViewPurchaseOrder $subject, $result, $purchaseOrder)
    {
        if ($result) {
            return $result;
        }
        if ($this->companyAdminPermission->isGivenUserCompanyAdmin($this->companyContext->getCustomerId()) &&
            $this->companyStructure->getAllowedChildrenIds((int)$this->companyContext->getCustomerId())
        ) {
            return true;
        }

        $userRoles = $this->userRoleManagement->getRolesByUserId($this->companyContext->getCustomerId());
        /** @var UserRole $role */
        $role = reset($userRoles);
        $appliedRules = $this->appliedRuleRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(AppliedRuleInterface::KEY_PURCHASE_ORDER_ID, $purchaseOrder->getEntityId())
                ->create()
        );
        $isAllowed = false;
        foreach ($appliedRules->getItems() as $rule) {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$rule->getId());
            /* @var AppliedRuleApproverInterface $approver */
            foreach ($approvers->getItems() as $approver) {
                if ($this->userCanView($approver, (int)$role->getRoleId(), (string)$purchaseOrder->getCreatorId())) {
                    $isAllowed = true;
                    break;
                }
            }
            if ($isAllowed) {
                break;
            }
        }

        return $isAllowed;
    }

    /**
     * Determine if the customer can satisfy the approver type.
     *
     * @param AppliedRuleApproverInterface $approver
     * @param int $roleId
     * @param string $creatorId
     * @return bool
     * @throws LocalizedException
     */
    private function userCanView(
        AppliedRuleApproverInterface $approver,
        int $roleId,
        string $creatorId
    ) {
        $approverType = $approver->getApproverType();
        if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ROLE) {
            return ($approver->getRoleId() == $roleId);
        } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
            return $this->userIsManager($creatorId);
        } else {
            return false;
        }
    }

    /**
     * Determine if the user is a superior of the purchase order creator.
     *
     * @param string $creatorId
     * @return bool
     * @throws LocalizedException
     */
    private function userIsManager(string $creatorId) : bool
    {
        return in_array(
            $creatorId,
            $this->companyStructure->getAllowedChildrenIds((int)$this->companyContext->getCustomerId())
        );
    }
}
