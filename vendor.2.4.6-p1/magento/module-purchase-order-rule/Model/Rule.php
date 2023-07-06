<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleExtensionInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\Rule as ResourceRule;
use Magento\PurchaseOrderRule\Model\Rule\Condition\CombineFactory;
use Magento\Rule\Model\AbstractModel as RuleAbstract;
use Magento\Rule\Model\Action\CollectionFactory;

/**
 * Purchase Order Rule which new POs are validated against
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rule extends RuleAbstract implements RuleInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'purchase_order_rule';

    /**
     * @var CombineFactory
     */
    private $conditionCombineFactory;

    /**
     * @var CollectionFactory
     */
    private $conditionActionFactory;

    /**
     * @var PoisonPillPutInterface
     */
    private $pillPut;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     * @param CombineFactory $conditionCombineFactory
     * @param CollectionFactory $conditionActionFactory
     * @param PoisonPillPutInterface $pillPut
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        CombineFactory $conditionCombineFactory,
        CollectionFactory $conditionActionFactory,
        PoisonPillPutInterface $pillPut,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );

        $this->conditionCombineFactory = $conditionCombineFactory;
        $this->conditionActionFactory = $conditionActionFactory;
        $this->pillPut = $pillPut;
    }

    /**
     * Initialize resource mode
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceRule::class);
    }

    /**
     * @inheritDoc
     */
    public function getConditionsInstance()
    {
        return $this->conditionCombineFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getActionsInstance()
    {
        return $this->conditionActionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->getData(self::KEY_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): RuleInterface
    {
        return $this->setData(self::KEY_NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::KEY_DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(string $description): RuleInterface
    {
        return $this->setData(self::KEY_DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->getData(self::KEY_IS_ACTIVE) === '1';
    }

    /**
     * @inheritDoc
     */
    public function setIsActive(bool $isActive): RuleInterface
    {
        return $this->setData(self::KEY_IS_ACTIVE, $isActive);
    }

    /**
     * @inheritDoc
     */
    public function getCompanyId(): int
    {
        return (int) $this->getData(self::KEY_COMPANY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCompanyId(int $companyId): RuleInterface
    {
        return $this->setData(self::KEY_COMPANY_ID, $companyId);
    }

    /**
     * @inheritDoc
     */
    public function getConditionsSerialized(): string
    {
        return $this->getData(self::KEY_CONDITIONS_SERIALIZED);
    }

    /**
     * @inheritDoc
     */
    public function setConditionsSerialized(string $conditions): RuleInterface
    {
        return $this->setData(self::KEY_CONDITIONS_SERIALIZED, $conditions);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt() : string
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt) : RuleInterface
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt() : string
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt) : RuleInterface
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedBy() : int
    {
        return (int) $this->getData(self::KEY_CREATED_BY);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedBy(int $customerId) : RuleInterface
    {
        return $this->setData(self::KEY_CREATED_BY, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getApproverRoleIds() : array
    {
        if ($this->getData(self::KEY_APPROVER_ROLE_IDS) !== null) {
            return $this->getData(self::KEY_APPROVER_ROLE_IDS);
        }

        if (!$this->getId()) {
            return [];
        }

        $approvalRoleIds = $this->getResource()->getApproverRoleIds((int) $this->getId());
        $this->setData(self::KEY_APPROVER_ROLE_IDS, $approvalRoleIds);

        return $approvalRoleIds;
    }

    /**
     * @inheritDoc
     */
    public function setApproverRoleIds(array $roleIds) : RuleInterface
    {
        return $this->setData(self::KEY_APPROVER_ROLE_IDS, $roleIds);
    }

    /**
     * @inheritDoc
     */
    public function isAdminApprovalRequired() : bool
    {
        $requiresAdminApproval = $this->getData(self::KEY_REQUIRES_ADMIN_APPROVAL);
        if ($requiresAdminApproval === null) {
            $requiresAdminApproval = $this->getResource()->isAdminApprovalRequired((int) $this->getId());
            $this->setData(self::KEY_REQUIRES_ADMIN_APPROVAL, $requiresAdminApproval);
        }
        return $requiresAdminApproval;
    }

    /**
     * @inheritDoc
     */
    public function setAdminApprovalRequired(bool $requiresAdminApproval) : RuleInterface
    {
        return $this->setData(self::KEY_REQUIRES_ADMIN_APPROVAL, $requiresAdminApproval);
    }

    /**
     * @inheritDoc
     */
    public function isManagerApprovalRequired() : bool
    {
        $requiresManagerApproval = $this->getData(self::KEY_REQUIRES_MANAGER_APPROVAL);
        if ($requiresManagerApproval === null) {
            $requiresManagerApproval = $this->getResource()->isManagerApprovalRequired((int) $this->getId());
            $this->setData(self::KEY_REQUIRES_MANAGER_APPROVAL, $requiresManagerApproval);
        }
        return $requiresManagerApproval;
    }

    /**
     * @inheritDoc
     */
    public function setManagerApprovalRequired(bool $requiresManagerApproval) : RuleInterface
    {
        return $this->setData(self::KEY_REQUIRES_MANAGER_APPROVAL, $requiresManagerApproval);
    }

    /**
     * @inheritDoc
     */
    public function isAppliesToAll() : bool
    {
        return (bool) $this->getData(self::KEY_APPLIES_TO_ALL);
    }

    /**
     * @inheritDoc
     */
    public function setAppliesToAll(bool $appliesToAll) : RuleInterface
    {
        return $this->setData(self::KEY_APPLIES_TO_ALL, $appliesToAll);
    }

    /**
     * @inheritDoc
     */
    public function getAppliesToRoleIds() : array
    {
        if ($this->getData(self::KEY_APPLIES_TO_ROLE_IDS) !== null) {
            return $this->getData(self::KEY_APPLIES_TO_ROLE_IDS);
        }

        if (!$this->getId()) {
            return [];
        }

        $appliesToRoleIds = $this->getResource()->getAppliesToRoleIds((int) $this->getId());
        $this->setData(self::KEY_APPLIES_TO_ROLE_IDS, $appliesToRoleIds);

        return $appliesToRoleIds;
    }

    /**
     * @inheritDoc
     */
    public function setAppliesToRoleIds(array $roleIds) : RuleInterface
    {
        return $this->setData(self::KEY_APPLIES_TO_ROLE_IDS, $roleIds);
    }

    /**
     * After save ensure we update the associated approver role IDs
     *
     * @return AbstractExtensibleModel
     * @throws \Exception
     */
    public function afterSave()
    {
        $this->getResource()->saveApproverRoleIds((int) $this->getId(), $this->getApproverRoleIds());
        $this->getResource()->setAdminApprovalRequired((int) $this->getId(), $this->isAdminApprovalRequired());
        $this->getResource()->setManagerApprovalRequired((int) $this->getId(), $this->isManagerApprovalRequired());
        $this->getResource()->saveAppliesTo((int) $this->getId(), $this->getAppliesToRoleIds());
        $this->pillPut->put();
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
    public function setExtensionAttributes(RuleExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
