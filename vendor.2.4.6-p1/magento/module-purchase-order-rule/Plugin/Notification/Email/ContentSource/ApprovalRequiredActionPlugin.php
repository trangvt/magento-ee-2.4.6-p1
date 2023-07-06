<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Plugin\Notification\Email\ContentSource;

use Magento\Company\Api\RoleManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Model\Notification\Email\ContentSource\ApprovalRequiredAction;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\AddingApproverRolesProcessor;

/**
 * Plugin to restrict action to remaining rule approvers and company admin
 */
class ApprovalRequiredActionPlugin
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
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var RoleManagementInterface
     */
    private $roleManagement;

    /**
     * @var AddingApproverRolesProcessor
     */
    private $addingApproverRolesProcessor;

    /**
     * @param AppliedRuleRepositoryInterface $appliedRuleRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param RoleRepositoryInterface $roleRepository
     * @param RoleManagementInterface $roleManagement
     * @param AddingApproverRolesProcessor $addingApproverRolesProcessor
     */
    public function __construct(
        AppliedRuleRepositoryInterface $appliedRuleRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        RoleRepositoryInterface $roleRepository,
        RoleManagementInterface $roleManagement,
        AddingApproverRolesProcessor $addingApproverRolesProcessor
    ) {
        $this->appliedRuleRepository = $appliedRuleRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->roleRepository = $roleRepository;
        $this->roleManagement = $roleManagement;
        $this->addingApproverRolesProcessor = $addingApproverRolesProcessor ?:
            ObjectManager::getInstance()->get(AddingApproverRolesProcessor::class);
    }

    /**
     * After ApprovalRequiredAction::getTemplateVars; add approvers roles
     *
     * @param ApprovalRequiredAction $subject
     * @param DataObject $result
     * @return DataObject
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTemplateVars(ApprovalRequiredAction $subject, DataObject $result): DataObject
    {
        $purchaseOrderId = (int)$result->getPurchaseOrderId();
        $appliedRules =  $this->appliedRuleRepository->getListByPurchaseOrderId($purchaseOrderId);
        $roleNames = $this->addingApproverRolesProcessor->process($appliedRules);

        if (count($roleNames) > 0) {
            $result->setApproversFullNames(join(', ', array_unique($roleNames)));
        }

        return $result;
    }
}
