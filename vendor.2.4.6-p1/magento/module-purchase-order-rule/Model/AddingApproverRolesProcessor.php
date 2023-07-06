<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;

/**
 * Class to restrict action to remaining rule approvers and company admin
 */
class AddingApproverRolesProcessor
{
    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * AddingApproverRolesProcessor constructor.
     *
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param RoleRepositoryInterface $roleRepository
     */
    public function __construct(
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        RoleRepositoryInterface $roleRepository
    ) {
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Add approver roles
     *
     * @param SearchResultsInterface $appliedRules
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function process(SearchResultsInterface $appliedRules)
    {
        $roleNames = [];
        foreach ($appliedRules->getItems() as $appliedRule) {
            $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int)$appliedRule->getId());

            foreach ($approvers->getItems() as $appliedRuleApprover) {
                $approverType = $appliedRuleApprover->getApproverType();
                /** @var AppliedRuleApproverInterface $appliedRuleApprover */
                if ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ROLE) {
                    $roleNames[] = $this->roleRepository->get($appliedRuleApprover->getRoleId())->getRoleName();
                } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN) {
                    $roleNames[] = __('Your Company Administrator');
                } elseif ($approverType === AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER) {
                    $roleNames[] = __('Your Manager');
                }
            }
        }

        return $roleNames;
    }
}
