<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Processor;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\UserRole;
use Magento\Company\Model\UserRoleManagement;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Processor\ApprovalProcessorInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagementInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;

/**
 * Determine whether all applied rules have been approved
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleApproval implements ApprovalProcessorInterface
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
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var LogManagementInterface
     */
    private $purchaseOrderLogManagement;

    /**
     * @var AclInterface
     */
    private $companyAcl;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var PurchaseOrderRepositoryInterface|null
     */
    private $purchaseOrderRepository;

    /**
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param UserRoleManagement $userRoleManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param Structure $companyStructure
     * @param CompanyRepositoryInterface $companyRepository
     * @param LogManagementInterface $purchaseOrderLogManagement
     * @param AclInterface $companyAcl
     * @param AuthorizationInterface $authorization
     * @param PurchaseOrderRepositoryInterface|null $purchaseOrderRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        UserRoleManagement $userRoleManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        Structure $companyStructure,
        CompanyRepositoryInterface $companyRepository,
        LogManagementInterface $purchaseOrderLogManagement,
        AclInterface $companyAcl,
        AuthorizationInterface $authorization,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository = null
    ) {
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->userRoleManagement = $userRoleManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->companyStructure = $companyStructure;
        $this->companyRepository = $companyRepository;
        $this->purchaseOrderLogManagement = $purchaseOrderLogManagement;
        $this->companyAcl = $companyAcl;
        $this->authorization = $authorization;
        $this->purchaseOrderRepository = $purchaseOrderRepository ?: ObjectManager::getInstance()->get(
            PurchaseOrderRepositoryInterface::class
        );
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function processApproval(PurchaseOrderInterface $purchaseOrder, int $customerId)
    {
        $userRoles = $this->userRoleManagement->getRolesByUserId($customerId);
        /** @var UserRole $role */
        $role = current($userRoles);
        $company = $this->companyRepository->get((int) $purchaseOrder->getCompanyId());
        $companyAdminId = $company->getSuperUserId();
        $rulesToApprove = $this->appliedRuleRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(AppliedRuleInterface::KEY_PURCHASE_ORDER_ID, $purchaseOrder->getEntityId())
                ->create()
        );
        $approved = false;
        $approvedAll = true;
        foreach ($rulesToApprove->getItems() as $rule) {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$rule->getId());
            /* @var AppliedRuleApproverInterface $approver */
            foreach ($approvers->getItems() as $approver) {
                if ($approver->getStatus() === AppliedRuleApproverInterface::STATUS_PENDING &&
                    $this->customerCanApprove(
                        $approver,
                        (int)$role->getId(),
                        $customerId,
                        (int)$purchaseOrder->getCreatorId(),
                        (int)$companyAdminId
                    )
                ) {
                    $approved = true;
                    $approver->approve($customerId);
                    $this->appliedRuleApproverRepository->save($approver);
                    $purchaseOrder->setUpdatedAt($approver->getUpdatedAt());
                    $this->purchaseOrderRepository->save($purchaseOrder);
                    $approvedAll &= true;
                } elseif ($approver->getStatus() === AppliedRuleApproverInterface::STATUS_APPROVED) {
                    $approvedAll &= true;
                } else {
                    $approvedAll = false;
                }
            }
        }
        // only log the approval once, even if it applies to multiple rules
        if ($approved && !$approvedAll && !$this->isCurrentUserSuperApprover()) {
            $this->purchaseOrderLogManagement->logAction(
                $purchaseOrder,
                'approve',
                [
                    'increment_id' => $purchaseOrder->getIncrementId()
                ],
                $customerId
            );
        }

        if ($purchaseOrder->getStatus() !== PurchaseOrderInterface::STATUS_APPROVED &&
            ($approvedAll || $this->isCurrentUserSuperApprover())
        ) {
            $this->purchaseOrderManagement->approvePurchaseOrder($purchaseOrder, $customerId);
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
    private function customerCanApprove(
        AppliedRuleApproverInterface $approver,
        int $roleId,
        int $customerId,
        int $creatorId,
        int $companyAdminId
    ) {
        $approverType = $approver->getApproverType();
        if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ROLE) {
            $canApprovePurchaseOrder = true;
            $activeUsers = 0;

            if ($customerId === $creatorId) {
                foreach ($this->companyAcl->getUsersByRoleId($roleId) as $user) {
                    $activeUsers += (
                        $user->getExtensionAttributes()->getCompanyAttributes()->getStatus() ==
                        CompanyCustomerInterface::STATUS_ACTIVE
                    ) ?
                        1 : 0;
                    if ($activeUsers > 1) {
                        $canApprovePurchaseOrder = false;
                        break;
                    }
                }
            }

            return ($approver->getRoleId() == $roleId) && $canApprovePurchaseOrder;
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

    /**
     * Check permission for super approver.
     *
     * @return bool
     */
    private function isCurrentUserSuperApprover(): bool
    {
        return $this->authorization->isAllowed('Magento_PurchaseOrderRule::super_approve_purchase_order');
    }
}
