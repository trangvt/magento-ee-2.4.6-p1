<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrderRule\Plugin;

use Magento\Company\Model\CompanyRepository;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\UserRole;
use Magento\Company\Model\UserRoleManagement;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderManagement as Subject;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;

/**
 * Plugin to purchase order rejection to ensure approvers are updated correctly
 */
class PurchaseOrderManagement
{
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
     * @var CompanyRepository
     */
    private $companyRepository;

    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * PurchaseOrderManagement constructor.
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param UserRoleManagement $userRoleManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param CompanyRepository $companyRepository
     * @param Structure $companyStructure
     */
    public function __construct(
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        UserRoleManagement $userRoleManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        CompanyRepository $companyRepository,
        Structure $companyStructure
    ) {
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->userRoleManagement = $userRoleManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->companyRepository = $companyRepository;
        $this->companyStructure = $companyStructure;
    }

    /**
     * After reject purchase order update rule approvers
     *
     * @param Subject $subject
     * @param mixed $result
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int $actorId
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRejectPurchaseOrder(
        Subject $subject,
        $result,
        PurchaseOrderInterface $purchaseOrder,
        $actorId = null
    ) {
        // We do not reject an approver if the order is being auto rejected
        if ($actorId) {
            $company = $this->companyRepository->get((int) $purchaseOrder->getCompanyId());
            $companyAdminId = $company->getSuperUserId();
            $userRoles = $this->userRoleManagement->getRolesByUserId($actorId);
            /** @var UserRole $role */
            $role = current($userRoles);
            $rulesToApprove = $this->appliedRuleRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilter(AppliedRuleInterface::KEY_PURCHASE_ORDER_ID, $purchaseOrder->getEntityId())
                    ->create()
            );
            foreach ($rulesToApprove->getItems() as $rule) {
                $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$rule->getId());
                /* @var AppliedRuleApproverInterface $approver */
                foreach ($approvers->getItems() as $approver) {
                    if ($approver->getStatus() === AppliedRuleApproverInterface::STATUS_PENDING
                        && $this->userCanReject(
                            $approver,
                            $role->getRoleId(),
                            $actorId,
                            $purchaseOrder->getCreatorId(),
                            $companyAdminId
                        )
                    ) {
                        $approver->reject($actorId);
                        $this->appliedRuleApproverRepository->save($approver);
                    }
                }
            }
        }
    }

    /**
     * Determine if the customer can satisfy the approver type.
     *
     * @param AppliedRuleApproverInterface $approver
     * @param int $roleId
     * @param int $customerId
     * @param int $creatorId
     * @param int $companyAdminId
     * @return bool
     * @throws LocalizedException
     */
    private function userCanReject(
        AppliedRuleApproverInterface $approver,
        int $roleId,
        int $customerId,
        int $creatorId,
        int $companyAdminId
    ) {
        $approverType = $approver->getApproverType();
        if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ROLE) {
            return ($approver->getRoleId() == $roleId);
        } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN) {
            return $customerId === $companyAdminId;
        } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
            return $this->customerIsManager($customerId, $creatorId);
        } else {
            return false;
        }
    }

    /**
     * Determine if the user is a superior of the purchase order creator.
     *
     * @param int $customerId
     * @param int $creatorId
     * @return bool
     * @throws LocalizedException
     */
    private function customerIsManager(int $customerId, int $creatorId) : bool
    {
        return in_array(
            (string)$creatorId,
            $this->companyStructure->getAllowedChildrenIds($customerId)
        );
    }
}
