<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrderRule\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Purchase Order approval rules interface
 *
 * @api
 * @since 100.2.0
 */
interface AppliedRuleInterface extends ExtensibleDataInterface
{
    const KEY_ID = 'applied_rule_id';

    const KEY_PURCHASE_ORDER_ID = 'purchase_order_id';

    const KEY_RULE_ID = 'rule_id';

    const KEY_CREATED_AT = 'create_at';

    const KEY_APPROVER_ROLE_IDS = 'approver_role_ids';

    const KEY_REQUIRES_ADMIN_APPROVAL = 'requires_admin_approval';

    const KEY_REQUIRES_MANAGER_APPROVAL = 'requires_manager_approval';

    /**
     * Get Rule ID
     *
     * @return int|null
     * @since 100.2.0
     */
    public function getId();

    /**
     * Return the Purchase Order ID
     *
     * @return int
     * @since 100.2.0
     */
    public function getPurchaseOrderId() : int;

    /**
     * Set the purchase order ID for this applied rule
     *
     * @param int $purchaseOrderId
     * @return AppliedRuleInterface
     * @since 100.2.0
     */
    public function setPurchaseOrderId(int $purchaseOrderId) : AppliedRuleInterface;

    /**
     * Retrieve the instance of the Purchase Order
     *
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getPurchaseOrder() : PurchaseOrderInterface;

    /**
     * Return the rule ID
     *
     * @return int
     * @since 100.2.0
     */
    public function getRuleId() : int;

    /**
     * Set the rule ID for the aplied rule
     *
     * @param int $ruleId
     * @return AppliedRuleInterface
     * @since 100.2.0
     */
    public function setRuleId(int $ruleId) : AppliedRuleInterface;

    /**
     * Retrieve the associated rule for this applied rule
     *
     * @return RuleInterface
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getRule() : RuleInterface;

    /**
     * Return the created at date
     *
     * @return string
     * @since 100.2.0
     */
    public function getCreatedAt() : string;

    /**
     * Set the approver role IDs for this applied rule
     *
     * @param array $roleIds
     * @return AppliedRuleInterface
     * @since 100.2.0
     */
    public function setApproverRoleIds(array $roleIds) : AppliedRuleInterface;

    /**
     * Has this applied rule been approved?
     *
     * @return bool
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function isApproved() : bool;

    /**
     * Set whether the rule requires admin approval
     *
     * @param bool $requiresAdminApproval
     * @return AppliedRuleInterface
     * @since 100.2.0
     */
    public function setAdminApprovalRequired(bool $requiresAdminApproval) : AppliedRuleInterface;

    /**
     * Set whether the rule requires manager approval
     *
     * @param bool $requiresManagerApproval
     * @return AppliedRuleInterface
     * @since 100.2.0
     */
    public function setManagerApprovalRequired(bool $requiresManagerApproval) : AppliedRuleInterface;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\PurchaseOrderRule\Api\Data\AppliedRuleExtensionInterface|null
     * @since 100.2.0
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\PurchaseOrderRule\Api\Data\AppliedRuleExtensionInterface $extensionAttributes
     * @return $this
     * @since 100.2.0
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrderRule\Api\Data\AppliedRuleExtensionInterface $extensionAttributes
    );
}
