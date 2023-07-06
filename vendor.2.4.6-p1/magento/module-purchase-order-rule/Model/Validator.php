<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagementInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterfaceFactory;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleSearchResultsInterface;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\PurchaseOrder\Model\Processor\ApprovalProcessorInterface;
use Magento\PurchaseOrder\Model\Processor\Exception\ApprovalProcessorException;

/**
 * Rule validator to run a Purchase Order through the rules engine
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Validator
{
    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @var AppliedRuleInterfaceFactory
     */
    private $appliedRuleFactory;

    /**
     * @var AppliedRuleRepositoryInterface
     */
    private $appliedRuleRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var AclInterface
     */
    private $companyAcl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LogManagementInterface
     */
    private $purchaseOrderLogManagement;

    /**
     * @var ApprovalProcessorInterface
     */
    private $purchaseOrderApprovalsProcessor;

    /**
     * @param RuleRepositoryInterface $ruleRepository
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param AppliedRuleInterfaceFactory $appliedRuleFactory
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param AclInterface $companyAcl
     * @param LoggerInterface $logger
     * @param LogManagementInterface $purchaseOrderLogManagement
     * @param ApprovalProcessorInterface $purchaseOrderApprovalsProcessor
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        RuleRepositoryInterface $ruleRepository,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        AppliedRuleInterfaceFactory $appliedRuleFactory,
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        AclInterface $companyAcl,
        LoggerInterface $logger,
        LogManagementInterface $purchaseOrderLogManagement,
        ApprovalProcessorInterface $purchaseOrderApprovalsProcessor
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->appliedRuleFactory = $appliedRuleFactory;
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->companyAcl = $companyAcl;
        $this->logger = $logger;
        $this->purchaseOrderLogManagement = $purchaseOrderLogManagement;
        $this->purchaseOrderApprovalsProcessor = $purchaseOrderApprovalsProcessor;
    }

    /**
     * Validate a purchase order against rules defined by the company
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @throws LocalizedException
     */
    public function validate(PurchaseOrderInterface $purchaseOrder)
    {
        $rules = $this->getRulesForPurchaseOrder($purchaseOrder);

        // If the company hasn't configured any rules we can approve the Purchase Order
        if ($rules->getTotalCount() === 0) {
            $this->purchaseOrderManagement->approvePurchaseOrder($purchaseOrder);
        } else {
            $rulesToApply = [];
            /* @var Rule $rule */
            foreach ($rules->getItems() as $rule) {
                // If the rule matches it means it requires further approval
                if ($rule->getConditions()->validate($purchaseOrder)) {
                    $rulesToApply[] = $rule;
                }
            }

            if (count($rulesToApply) > 0) {
                /* @var Rule $ruleToApply */
                foreach ($rulesToApply as $ruleToApply) {
                    // Apply this rule to the purchase order and record which roles are required to approve
                    /** @var AppliedRule $appliedRuleInstance */
                    $appliedRuleInstance = $this->appliedRuleFactory->create();
                    $appliedRuleInstance->setPurchaseOrderId((int)$purchaseOrder->getEntityId())
                        ->setRuleId((int)$ruleToApply->getId())
                        ->setApproverRoleIds($ruleToApply->getApproverRoleIds());
                    if ($ruleToApply->isAdminApprovalRequired()) {
                        $appliedRuleInstance->setAdminApprovalRequired($ruleToApply->isAdminApprovalRequired());
                    }
                    if ($ruleToApply->isManagerApprovalRequired()) {
                        $appliedRuleInstance->setManagerApprovalRequired($ruleToApply->isManagerApprovalRequired());
                    }
                    $this->appliedRuleRepository->save($appliedRuleInstance);
                    $this->purchaseOrderLogManagement->logAction(
                        $purchaseOrder,
                        'apply_rules',
                        [
                            'rule' => $ruleToApply->getName()
                        ],
                        null
                    );
                }

                $approved = $this->processSameRoleAutoApproval($purchaseOrder, $rulesToApply);
                if (!$approved) {
                    // Ensure the purchase order is marked as requiring approval
                    $this->purchaseOrderManagement->setApprovalRequired($purchaseOrder);
                }
            } else {
                // If no rules match we're good to approve the purchase order
                $this->purchaseOrderManagement->approvePurchaseOrder($purchaseOrder);
            }
        }
    }

    /**
     * If the rules that matched require the approval of the purchase orders creator role, try to approve them
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param array $rulesToApply
     * @return bool
     * @throws ApprovalProcessorException
     * @throws LocalizedException
     */
    private function processSameRoleAutoApproval(PurchaseOrderInterface $purchaseOrder, array $rulesToApply)
    {
        $roleIds = array_merge(
            ...array_map(
                function (Rule $rule) {
                    return $rule->getApproverRoleIds();
                },
                $rulesToApply
            )
        );
        $uniqueRoleIds = array_unique($roleIds);

        $userRoles = $this->companyAcl->getRolesByUserId((int) $purchaseOrder->getCreatorId());
        $userRoleIds = array_map(function (RoleInterface $role) {
            return $role->getId();
        }, $userRoles, $userRoles);

        if (count($uniqueRoleIds) > 0 && !empty(array_intersect($userRoleIds, $uniqueRoleIds))) {
            $this->purchaseOrderApprovalsProcessor->processApproval(
                $purchaseOrder,
                (int) $purchaseOrder->getCreatorId()
            );

            return $purchaseOrder->getAutoApproved() === true;
        }

        return false;
    }

    /**
     * Retrieve all associated rules for the purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return RuleSearchResultsInterface
     * @throws LocalizedException
     */
    private function getRulesForPurchaseOrder(PurchaseOrderInterface $purchaseOrder)
    {
        $userRoles = $this->companyAcl->getRolesByUserId($purchaseOrder->getCreatorId());
        $userRoleIds = array_map(function (RoleInterface $role) {
            return $role->getId();
        }, $userRoles);

        return $this->ruleRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(RuleInterface::KEY_COMPANY_ID, (int) $purchaseOrder->getCompanyId())
                ->addFilter(RuleInterface::KEY_APPLIES_TO_ROLE_IDS, $userRoleIds, 'in')
                ->addFilter(RuleInterface::KEY_IS_ACTIVE, true)
                ->create()
        );
    }
}
