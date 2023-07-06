<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrderRule\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Purchase Order approval rules interface
 *
 * @api
 * @since 100.2.0
 */
interface AppliedRuleApproverInterface extends ExtensibleDataInterface
{
    const KEY_ID = 'applied_rule_approver_id';

    const KEY_APPLIED_RULE_ID = 'applied_rule_id';

    const KEY_ROLE_ID = 'role_id';

    const KEY_APPROVER_TYPE = 'approver_type';

    const KEY_STATUS = 'status';

    const KEY_CUSTOMER_ID = 'customer_id';

    const KEY_UPDATED_AT = 'updated_at';

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    const APPROVER_TYPE_ADMIN = 'admin';

    const APPROVER_TYPE_MANAGER = 'manager';

    const APPROVER_TYPE_ROLE = 'role';

    /**
     * Get Rule ID
     *
     * @return int|null
     * @since 100.2.0
     */
    public function getId();

    /**
     * Return the applied rule ID
     *
     * @return int
     * @since 100.2.0
     */
    public function getAppliedRuleId() : int;

    /**
     * Set the applied rule ID for this applied rule approval
     *
     * @param int $appliedRuleId
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function setAppliedRuleId(int $appliedRuleId) : AppliedRuleApproverInterface;

    /**
     * Return the role ID
     *
     * @return int
     * @since 100.2.0
     */
    public function getRoleId() : int;

    /**
     * Set the role ID
     *
     * @param int $roleId
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function setRoleId(int $roleId) : AppliedRuleApproverInterface;

    /**
     * Get the approver type.
     *
     * @return string
     * @since 100.2.0
     */
    public function getApproverType() : string;

    /**
     * Set the approver type.
     *
     * @param string $approverType
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function setApproverType(string $approverType) : AppliedRuleApproverInterface;

    /**
     * Return whether this approver has approved the applied rule
     *
     * @return int
     * @since 100.2.0
     */
    public function getStatus() : int;

    /**
     * Set whether the approver approved this applied rule
     *
     * @param int $status
     * @return mixed
     * @since 100.2.0
     */
    public function setStatus(int $status) : AppliedRuleApproverInterface;

    /**
     * Return the customer ID of whom approved the applied rule
     *
     * @return int|null
     * @since 100.2.0
     */
    public function getCustomerId() : ?int;

    /**
     * Set the customer ID who acted upon this approver line
     *
     * @param int $customerId
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function setCustomerId(int $customerId) : AppliedRuleApproverInterface;

    /**
     * Return the time at which the order was approved
     *
     * @return string|null
     * @since 100.2.0
     */
    public function getUpdatedAt() : ?string;

    /**
     * Set the date which this approver approved the applied rule
     *
     * @param string $updatedAt
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function setUpdatedAt(string $updatedAt) : AppliedRuleApproverInterface;

    /**
     * Approve this approver instance for the applied rule
     *
     * @param int $customerId
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function approve(int $customerId) : AppliedRuleApproverInterface;

    /**
     * Approve this approver instance for the applied rule
     *
     * @param int $customerId
     * @return AppliedRuleApproverInterface
     * @since 100.2.0
     */
    public function reject(int $customerId) : AppliedRuleApproverInterface;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverExtensionInterface|null
     * @since 100.2.0
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverExtensionInterface $extensionAttributes
     * @return $this
     * @since 100.2.0
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverExtensionInterface $extensionAttributes
    );
}
