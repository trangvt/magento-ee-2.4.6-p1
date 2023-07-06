<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\PurchaseOrder\Grid\Collection;

/**
 * Applied rule approver resource model
 */
class AppliedRuleApprover extends AbstractDb
{
    /**
     * @var GetPurchaseOrderIdsByAppliedRole
     */
    private GetPurchaseOrderIdsByAppliedRole $getPurchaseOrderIdsByAppliedRole;

    /**
     * @var GetPurchaseOrdersRequireApprovalByCurrentCustomer
     */
    private GetPurchaseOrdersRequireApprovalByCurrentCustomer $getPurchaseOrdersRequireApprovalByCurrentCustomer;

    /**
     * @param Context $context
     * @param GetPurchaseOrderIdsByAppliedRole $getPurchaseOrderIdsByAppliedRole
     * @param GetPurchaseOrdersRequireApprovalByCurrentCustomer $getPurchaseOrdersRequireApprovalByCurrentCustomer
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        GetPurchaseOrderIdsByAppliedRole $getPurchaseOrderIdsByAppliedRole,
        GetPurchaseOrdersRequireApprovalByCurrentCustomer $getPurchaseOrdersRequireApprovalByCurrentCustomer,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->getPurchaseOrderIdsByAppliedRole = $getPurchaseOrderIdsByAppliedRole;
        $this->getPurchaseOrdersRequireApprovalByCurrentCustomer = $getPurchaseOrdersRequireApprovalByCurrentCustomer;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('purchase_order_applied_rule_approver', AppliedRuleApproverInterface::KEY_ID);
    }

    /**
     * Get purchase order ids with requires approval form specific role.
     *
     * @param mixed $roleType
     * @param mixed|null $roleId
     * @return array
     * @throws LocalizedException
     */
    public function getPurchaseOrderIdsByAppliedRole($roleType, $roleId = null): array
    {
        return $this->getPurchaseOrderIdsByAppliedRole->execute(
            $roleType,
            $roleId
        );
    }

    /**
     * Get purchase orders that require approval by current customer.
     *
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPurchaseOrdersRequireApprovalByCurrentCustomer(): Collection
    {
        return $this->getPurchaseOrdersRequireApprovalByCurrentCustomer->execute();
    }
}
