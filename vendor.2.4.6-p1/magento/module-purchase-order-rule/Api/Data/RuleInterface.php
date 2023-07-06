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
interface RuleInterface extends ExtensibleDataInterface
{
    public const KEY_ID = 'rule_id';
    public const KEY_NAME = 'name';
    public const KEY_DESCRIPTION = 'description';
    public const KEY_IS_ACTIVE = 'is_active';
    public const KEY_COMPANY_ID = 'company_id';
    public const KEY_CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const KEY_APPROVER_ROLE_IDS = 'approver_role_ids';
    public const KEY_REQUIRES_ADMIN_APPROVAL = 'requires_admin_approval';
    public const KEY_REQUIRES_MANAGER_APPROVAL = 'requires_manager_approval';
    public const KEY_APPLIES_TO_ALL = 'applies_to_all';
    public const KEY_APPLIES_TO_ROLE_IDS = 'applies_to_role_ids';
    public const KEY_CREATED_AT = 'created_at';
    public const KEY_UPDATED_AT = 'updated_at';
    public const KEY_CREATED_BY = 'created_by';

    /**
     * Get Rule ID
     *
     * @return int|null
     * @since 100.2.0
     */
    public function getId();

    /**
     * Retrieve the rules name
     *
     * @return string
     * @since 100.2.0
     */
    public function getName() : string;

    /**
     * Set the rules name
     *
     * @param string $name
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setName(string $name) : RuleInterface;

    /**
     * Retrieve the rules description
     *
     * @return string|null
     * @since 100.2.0
     */
    public function getDescription() : ?string;

    /**
     * Set the rules description
     *
     * @param string $description
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setDescription(string $description) : RuleInterface;

    /**
     * Determine if the rule is active
     *
     * @return bool
     * @since 100.2.0
     */
    public function isActive() : bool;

    /**
     * Set the active status of the rule
     *
     * @param bool $active
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setIsActive(bool $active) : RuleInterface;

    /**
     * Retrieve the company ID for the rule
     *
     * @return int
     * @since 100.2.0
     */
    public function getCompanyId() : int;

    /**
     * Set the company ID for the rule
     *
     * @param int $companyId
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setCompanyId(int $companyId) : RuleInterface;

    /**
     * Retrieve the serialized conditions
     *
     * @return string
     * @since 100.2.0
     */
    public function getConditionsSerialized() : string;

    /**
     * Set the serialized conditions
     *
     * @param string $conditions
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setConditionsSerialized(string $conditions) : RuleInterface;

    /**
     * Retrieve the updated at date
     *
     * @return mixed
     * @since 100.2.0
     */
    public function getUpdatedAt() : string;

    /**
     * Set updated at date
     *
     * @param string $updatedAt
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setUpdatedAt(string $updatedAt) : RuleInterface;

    /**
     * Retrieve the approver role IDs
     *
     * @return string[]
     * @since 100.2.0
     */
    public function getApproverRoleIds() : array;

    /**
     * Set the approver role IDs
     *
     * @param array $roleIds
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setApproverRoleIds(array $roleIds) : RuleInterface;

    /**
     * Return whether the rule requires admin approval
     *
     * @return bool
     * @since 100.2.0
     */
    public function isAdminApprovalRequired() : bool;

    /**
     * Set whether the rule requires admin approval
     *
     * @param bool $requiresAdminApproval
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setAdminApprovalRequired(bool $requiresAdminApproval) : RuleInterface;

    /**
     * Return whether the rule requires manager approval
     *
     * @return bool
     * @since 100.2.0
     */
    public function isManagerApprovalRequired() : bool;

    /**
     * Set whether the rule requires manager approval
     *
     * @param bool $requiresManagerApproval
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setManagerApprovalRequired(bool $requiresManagerApproval) : RuleInterface;

    /**
     * Retrieve whether this rule applies to all
     *
     * @return bool
     * @since 100.2.0
     */
    public function isAppliesToAll() : bool;

    /**
     * Set whether or not this rule applies to all
     *
     * @param bool $appliesToAll
     * @return $this
     * @since 100.2.0
     */
    public function setAppliesToAll(bool $appliesToAll) : self;

    /**
     * Retrieve the
     *
     * @return string[]
     * @since 100.2.0
     */
    public function getAppliesToRoleIds() : array;

    /**
     * Set which roles this rule applies too
     *
     * @param array $roleIds
     * @return $this
     * @since 100.2.0
     */
    public function setAppliesToRoleIds(array $roleIds) : self;

    /**
     * Get the created at date
     *
     * @return string
     * @since 100.2.0
     */
    public function getCreatedAt() : string;

    /**
     * Set the created at date
     *
     * @param string $createdAt
     * @return RuleInterface
     * @since 100.2.0
     */
    public function setCreatedAt(string $createdAt) : RuleInterface;

    /**
     * Return the customer ID of rule creator
     *
     * @return int
     */
    public function getCreatedBy() : int;

    /**
     * Set the customer ID of rule creator
     *
     * @param int $customerId
     * @return RuleInterface
     */
    public function setCreatedBy(int $customerId) : RuleInterface;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\PurchaseOrderRule\Api\Data\RuleExtensionInterface|null
     * @since 100.2.0
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\PurchaseOrderRule\Api\Data\RuleExtensionInterface $extensionAttributes
     * @return $this
     * @since 100.2.0
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrderRule\Api\Data\RuleExtensionInterface $extensionAttributes
    );
}
