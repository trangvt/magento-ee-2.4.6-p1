<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Notification\Action\Recipient\Resolver;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\Action\Recipient\ResolverInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Model\AppliedRule;
use Magento\PurchaseOrderRule\Model\AppliedRuleApprover;

/**
 * Recipient Resolver for Purchase Order Rules to retrieve all required approvers from applied rules
 */
class RuleApprover implements ResolverInterface
{
    /**
     * @var AppliedRuleRepositoryInterface
     */
    private $appliedRuleRepository;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @var AclInterface
     */
    private $userRoleManagement;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param AclInterface $userRoleManagement
     * @param CompanyRepositoryInterface $companyRepository
     * @param Structure $companyStructure
     */
    public function __construct(
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        AclInterface $userRoleManagement,
        CompanyRepositoryInterface $companyRepository,
        Structure $companyStructure
    ) {
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->userRoleManagement = $userRoleManagement;
        $this->companyRepository = $companyRepository;
        $this->companyStructure = $companyStructure;
    }

    /**
     * @inheritDoc
     *
     * Retrieve all customer IDs associated with the approval of this purchase order based on the applied rules.
     *
     * @throws LocalizedException
     */
    public function getRecipients(PurchaseOrderInterface $purchaseOrder): array
    {
        $company = $this->companyRepository->get((int) $purchaseOrder->getCompanyId());
        $companyAdminId = $company->getSuperUserId();

        /** @var SearchResults $appliedRules */
        $appliedRules = $this->appliedRuleRepository->getListByPurchaseOrderId((int)$purchaseOrder->getEntityId());
        if ($appliedRules->getTotalCount() > 0) {
            $approvers = [];
            $roleIds = $this->getRoleIdsFromAppliedRules($appliedRules);
            if (count($roleIds) > 0) {
                $approvers = $this->getApproverIdsFromRoleIds($roleIds, (int)$purchaseOrder->getCreatorId());
            }
            if ($this->requiresAdminApproval($appliedRules)) {
                $approvers[] = $companyAdminId;
            }
            if ($this->requiresManagerApproval($appliedRules)) {
                $approvers[] = $this->getManagerApproverId((int)$purchaseOrder->getCreatorId());
            }
            if (count($approvers) > 0) {
                return array_unique($approvers);
            }
        }

        /**
         * If no rules applied to the PO or all applied rules had empty roles we and the PO isn't in approved status we
         * need to notify the company admin to approve the PO.
         */
        if ($purchaseOrder->getStatus() !== PurchaseOrderInterface::STATUS_APPROVED) {
            return [$companyAdminId];
        }

        // If no rules matched & the purchase order is approved we shouldn't be sending emails.
        return [];
    }

    /**
     * Retrieve all unique role IDs from a search result of applied rules
     *
     * @param SearchResults $appliedRules
     * @return array
     * @throws LocalizedException
     */
    private function getRoleIdsFromAppliedRules(SearchResults $appliedRules): array
    {
        $roleIds = [];
        /** @var AppliedRule $appliedRule */
        foreach ($appliedRules->getItems() as $appliedRule) {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$appliedRule->getId());
            /** @var AppliedRuleApprover $appliedRuleApprover */
            foreach ($approvers->getItems() as $appliedRuleApprover) {
                if ($appliedRuleApprover->getRoleId()) {
                    $roleIds[] = $appliedRuleApprover->getRoleId();
                }
            }
        }

        return array_unique($roleIds);
    }

    /**
     * Retrieve the approver IDs from the role IDs
     *
     * @param array $roleIds
     * @param int $creatorId
     * @return array
     */
    private function getApproverIdsFromRoleIds(array $roleIds, int $creatorId): array
    {
        $approvers = [];
        foreach ($roleIds as $roleId) {
            $roleApprovers = $this->userRoleManagement->getUsersByRoleId($roleId);
            if (count($roleApprovers) > 0) {
                $approvers[] = $roleApprovers;
            }
        }

        if (count($approvers) > 0) {
            $allApprovers = array_merge(...$approvers);

            // Retrieve all unique customer ID's from the approvers without PO creator if present
            return array_filter(
                array_unique(
                    array_map(
                        function (CustomerInterface $customer) {
                            return $customer->getId();
                        },
                        $allApprovers
                    )
                ),
                function (int $customerId) use ($creatorId) {
                    return $customerId != $creatorId;
                }
            );
        }

        return [];
    }

    /**
     * Determine if any of the applied rules require admin approval
     *
     * @param SearchResults $appliedRules
     * @return bool
     * @throws LocalizedException
     */
    private function requiresAdminApproval(SearchResults $appliedRules): bool
    {
        /** @var AppliedRule $appliedRule */
        foreach ($appliedRules->getItems() as $appliedRule) {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$appliedRule->getId());
            /** @var AppliedRuleApprover $appliedRuleApprover */
            foreach ($approvers->getItems() as $appliedRuleApprover) {
                if ($appliedRuleApprover->getApproverType() === AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if any of the applied rules require manager approval
     *
     * @param SearchResults $appliedRules
     * @return bool
     * @throws LocalizedException
     */
    private function requiresManagerApproval(SearchResults $appliedRules): bool
    {
        /** @var AppliedRule $appliedRule */
        foreach ($appliedRules->getItems() as $appliedRule) {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$appliedRule->getId());
            /** @var AppliedRuleApprover $appliedRuleApprover */
            foreach ($approvers->getItems() as $appliedRuleApprover) {
                if ($appliedRuleApprover->getApproverType() === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Retrieve the ID of the immediate manager of the PO creator.
     *
     * @param int $creatorId
     * @return int
     * @throws LocalizedException
     */
    private function getManagerApproverId(int $creatorId) : int
    {
        $creatorStructure = $this->companyStructure->getStructureByCustomerId($creatorId);
        $parentNode = $this->companyStructure->getTreeById($creatorStructure->getParentId());
        return (int)$parentNode->getEntityId();
    }
}
