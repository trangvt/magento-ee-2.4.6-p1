<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\PurchaseOrderRule\Model\AppliedRuleApproverFactory;
use Magento\PurchaseOrderRule\Model\AppliedRuleApprover;

/**
 * Applied rule resource model
 */
class AppliedRule extends AbstractDb
{
    /**
     * @var AppliedRuleApproverFactory
     */
    private $appliedRuleApproverFactory;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @param Context $context
     * @param AppliedRuleApproverFactory $appliedRuleApproverFactory
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param string $connectionName
     */
    public function __construct(
        Context $context,
        AppliedRuleApproverFactory $appliedRuleApproverFactory,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->appliedRuleApproverFactory = $appliedRuleApproverFactory;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('purchase_order_applied_rule', AppliedRuleInterface::KEY_ID);
    }

    /**
     * Save the approvals required for this applied rule
     *
     * @param int $appliedRuleId
     * @param array $roleIds
     * @throws LocalizedException
     */
    public function saveApproverRoleIds(int $appliedRuleId, array $roleIds)
    {
        foreach ($roleIds as $roleId) {
            /* @var AppliedRuleApprover $appliedRuleApprover */
            $appliedRuleApprover = $this->appliedRuleApproverFactory->create();
            $appliedRuleApprover->setAppliedRuleId($appliedRuleId)
                ->setApproverType(AppliedRuleApproverInterface::APPROVER_TYPE_ROLE)
                ->setRoleId((int) $roleId);
            $this->appliedRuleApproverRepository->save($appliedRuleApprover);
        }
    }

    /**
     * Save that admin approval is required for this applied rule
     *
     * @param int $appliedRuleId
     * @throws LocalizedException
     */
    public function saveAdminApprovalRequired(int $appliedRuleId)
    {
        /* @var AppliedRuleApprover $appliedRuleApprover */
        $appliedRuleApprover = $this->appliedRuleApproverFactory->create();
        $appliedRuleApprover->setAppliedRuleId($appliedRuleId)
            ->setApproverType(AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN);
        $this->appliedRuleApproverRepository->save($appliedRuleApprover);
    }

    /**
     * Save that manager approval is required for this applied rule
     *
     * @param int $appliedRuleId
     * @throws LocalizedException
     */
    public function saveManagerApprovalRequired(int $appliedRuleId)
    {
        /* @var AppliedRuleApprover $appliedRuleApprover */
        $appliedRuleApprover = $this->appliedRuleApproverFactory->create();
        $appliedRuleApprover->setAppliedRuleId($appliedRuleId)
            ->setApproverType(AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER);
        $this->appliedRuleApproverRepository->save($appliedRuleApprover);
    }
}
