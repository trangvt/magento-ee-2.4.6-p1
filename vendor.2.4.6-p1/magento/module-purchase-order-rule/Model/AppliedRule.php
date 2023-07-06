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
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleExtensionInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRule as ResourceAppliedRule;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;

/**
 * Purchase Order Rule which new POs are validated against
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AppliedRule extends AbstractExtensibleModel implements AppliedRuleInterface
{
    protected $_eventPrefix = 'purchase_order_applied_rule';

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var AppliedRuleApproverRepositoryInterface
     */
    private $appliedRuleApproverRepository;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository
     * @param RuleRepositoryInterface $ruleRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        AppliedRuleApproverRepositoryInterface $appliedRuleApproverRepository,
        RuleRepositoryInterface $ruleRepository,
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
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->appliedRuleApproverRepository = $appliedRuleApproverRepository;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * Initialize resource mode
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceAppliedRule::class);
    }

    /**
     * @inheritDoc
     */
    public function getPurchaseOrderId(): int
    {
        return (int) $this->getData(self::KEY_PURCHASE_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPurchaseOrderId(int $purchaseOrderId): AppliedRuleInterface
    {
        return $this->setData(self::KEY_PURCHASE_ORDER_ID, $purchaseOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getPurchaseOrder(): PurchaseOrderInterface
    {
        return $this->purchaseOrderRepository->getById($this->getPurchaseOrderId());
    }

    /**
     * @inheritDoc
     */
    public function getRuleId(): int
    {
        return (int) $this->getData(self::KEY_RULE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRuleId(int $ruleId): AppliedRuleInterface
    {
        return $this->setData(self::KEY_RULE_ID, $ruleId);
    }

    /**
     * @inheritDoc
     */
    public function getRule(): RuleInterface
    {
        return $this->ruleRepository->get($this->getRuleId());
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt() : string
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setApproverRoleIds(array $roleIds) : AppliedRuleInterface
    {
        return $this->setData(self::KEY_APPROVER_ROLE_IDS, $roleIds);
    }

    /**
     * @inheritDoc
     */
    public function isApproved() : bool
    {
        $approvers = $this->appliedRuleApproverRepository->getListByAppliedRuleId((int) $this->getId());
        if ($approvers->getTotalCount() > 0) {
            $approved = true;
            /* @var AppliedRuleApprover $approver */
            foreach ($approvers->getItems() as $approver) {
                if ($approver->getStatus() !== AppliedRuleApproverInterface::STATUS_APPROVED) {
                    $approved = false;
                    break;
                }
            }
            return $approved;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function setAdminApprovalRequired(bool $requiresAdminApproval) : AppliedRuleInterface
    {
        return $this->setData(self::KEY_REQUIRES_ADMIN_APPROVAL, $requiresAdminApproval);
    }

    /**
     * @inheritDoc
     */
    public function setManagerApprovalRequired(bool $requiresManagerApproval) : AppliedRuleInterface
    {
        return $this->setData(self::KEY_REQUIRES_MANAGER_APPROVAL, $requiresManagerApproval);
    }

    /**
     * @inheritDoc
     */
    public function afterSave()
    {
        if ($this->getData(self::KEY_APPROVER_ROLE_IDS)) {
            $this->getResource()->saveApproverRoleIds(
                (int) $this->getId(),
                $this->getData(self::KEY_APPROVER_ROLE_IDS)
            );
        }
        if ($this->getData(self::KEY_REQUIRES_ADMIN_APPROVAL) == 1) {
            $this->getResource()->saveAdminApprovalRequired((int) $this->getId());
        }
        if ($this->getData(self::KEY_REQUIRES_MANAGER_APPROVAL) == 1) {
            $this->getResource()->saveManagerApprovalRequired((int) $this->getId());
        }

        return parent::afterSave();
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
    public function setExtensionAttributes(AppliedRuleExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
