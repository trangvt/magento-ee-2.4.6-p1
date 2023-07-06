<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverExtensionInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover as ResourceAppliedRuleApprover;

/**
 * Purchase Order Rule which new POs are validated against
 */
class AppliedRuleApprover extends AbstractExtensibleModel implements AppliedRuleApproverInterface
{
    protected $_eventPrefix = 'purchase_order_applied_rule_approver';

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param DateTime $date
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        DateTime $date,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->date = $date;
    }

    /**
     * Initialize resource mode
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceAppliedRuleApprover::class);
    }

    /**
     * @inheritDoc
     */
    public function getAppliedRuleId() : int
    {
        return (int) $this->getData(self::KEY_APPLIED_RULE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setAppliedRuleId(int $appliedRuleId) : AppliedRuleApproverInterface
    {
        return $this->setData(self::KEY_APPLIED_RULE_ID, $appliedRuleId);
    }

    /**
     * @inheritDoc
     */
    public function getRoleId() : int
    {
        return (int) $this->getData(self::KEY_ROLE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRoleId(int $roleId) : AppliedRuleApproverInterface
    {
        return $this->setData(self::KEY_ROLE_ID, $roleId);
    }

    /**
     * @inheritDoc
     */
    public function getApproverType(): string
    {
        return $this->getData(self::KEY_APPROVER_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setApproverType(string $approverType): AppliedRuleApproverInterface
    {
        return $this->setData(self::KEY_APPROVER_TYPE, $approverType);
    }

    /**
     * @inheritDoc
     */
    public function getStatus() : int
    {
        return (int) $this->getData(self::KEY_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(int $status) : AppliedRuleApproverInterface
    {
        return $this->setData(self::KEY_STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId() : ?int
    {
        return (int) $this->getData(self::KEY_CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(int $customerId) : AppliedRuleApproverInterface
    {
        return $this->setData(self::KEY_CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt() : ?string
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $approvedAt) : AppliedRuleApproverInterface
    {
        return $this->setData(self::KEY_UPDATED_AT, $approvedAt);
    }

    /**
     * @inheritDoc
     */
    public function approve(int $customerId): AppliedRuleApproverInterface
    {
        return $this->setStatus(self::STATUS_APPROVED)
            ->setCustomerId($customerId)
            ->setUpdatedAt($this->date->gmtDate());
    }

    /**
     * @inheritDoc
     */
    public function reject(int $customerId): AppliedRuleApproverInterface
    {
        return $this->setStatus(self::STATUS_REJECTED)
            ->setCustomerId($customerId)
            ->setUpdatedAt($this->date->gmtDate());
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(AppliedRuleApproverExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
