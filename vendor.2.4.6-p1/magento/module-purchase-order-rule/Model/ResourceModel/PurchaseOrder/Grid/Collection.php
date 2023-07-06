<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\ResourceModel\PurchaseOrder\Grid;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\ResourceModel\Customer as CustomerResource;
use Magento\Company\Model\UserRoleManagement;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\Collection as PurchaseOrderCollection;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover;
use Psr\Log\LoggerInterface as Logger;

/**
 * Collection class for the purchase order that require approval grid.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends SearchResult
{
    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @var UserRoleManagement
     */
    private $userRoleManagement;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var AppliedRuleApprover
     */
    private $appliedRuleApprover;

    /**
     * @param CompanyContext $companyContext
     * @param CustomerResource $customerResource
     * @param CompanyStructure $companyStructure
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param UserRoleManagement $userRoleManagement
     * @param CompanyRepositoryInterface $companyRepository
     * @param AppliedRuleApprover $appliedRuleApprover
     * @param string $mainTable
     * @param string $resourceModel
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CompanyContext $companyContext,
        CustomerResource $customerResource,
        CompanyStructure $companyStructure,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        UserRoleManagement $userRoleManagement,
        CompanyRepositoryInterface $companyRepository,
        AppliedRuleApprover $appliedRuleApprover,
        $mainTable = 'purchase_order',
        $resourceModel = PurchaseOrderCollection::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->companyContext = $companyContext;
        $this->customerResource = $customerResource;
        $this->companyStructure = $companyStructure;
        $this->userRoleManagement = $userRoleManagement;
        $this->companyRepository = $companyRepository;
        $this->appliedRuleApprover = $appliedRuleApprover;
    }

    /**
     * @inheritDoc
     */
    public function _beforeLoad()
    {
        $this->applyDefaultJoinsToSelect();
        $this->applyDefaultFiltersToSelect();
        return parent::_beforeLoad();
    }

    /**
     * Apply the default JOIN clauses specific to the purchase order that require approval grid.
     */
    private function applyDefaultJoinsToSelect()
    {
        // Include data for the purchase order creator
        $this->getSelect()->joinLeft(
            ['customer_creator' => $this->getTable('customer_entity')],
            'customer_creator.entity_id = main_table.creator_id',
            ['creator_name' => "CONCAT(customer_creator.firstname, ' ', customer_creator.lastname)"]
        );
    }

    /**
     * Apply the default filters specific to the purchase order that require approval grid.
     *
     * @throws LocalizedException
     */
    private function applyDefaultFiltersToSelect()
    {
        $customerId = $this->companyContext->getCustomerId();
        $customerExtAttr = $this->customerResource->getCustomerExtensionAttributes($customerId);
        $companyId = isset($customerExtAttr['company_id']) ? $customerExtAttr['company_id'] : null;
        $this->addFieldToFilter('main_table.company_id', $companyId);

        if ($companyId === null) {
            return;
        }

        // Filter collection for purchase order that requires admin approval
        if ($this->isCurrentCustomerAdminCompanyAdmin($customerId, $companyId)) {
            $purchaseOrderIds = $this->appliedRuleApprover->getPurchaseOrderIdsByAppliedRole(
                AppliedRuleApproverInterface::APPROVER_TYPE_ADMIN
            );
            $this->addFieldToFilter('main_table.entity_id', ["in" => $purchaseOrderIds]);
            return;
        }

        $roles = $this->userRoleManagement->getRolesByUserId($customerId);
        $role = current($roles);
        // Get all purchase orders that requires approval for current role
        $purchaseOrderIds = $this->appliedRuleApprover->getPurchaseOrderIdsByAppliedRole(
            AppliedRuleApproverInterface::APPROVER_TYPE_ROLE,
            $role->getId()
        );

        // Get all purchase orders that requires manager approval
        if ($this->isCurrentCustomerHasSubordinates($customerId)) {
            $managerPurchaseOrderIds = $this->appliedRuleApprover->getPurchaseOrderIdsByAppliedRole(
                AppliedRuleApproverInterface::APPROVER_TYPE_MANAGER
            );
            $purchaseOrderIds = array_merge($managerPurchaseOrderIds, $purchaseOrderIds);
        }

        $this->addFieldToFilter('main_table.entity_id', ["in" => array_unique($purchaseOrderIds)]);
    }

    /**
     * Check if current user is company administrator.
     *
     * @param int $customerId
     * @param int $companyId
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isCurrentCustomerAdminCompanyAdmin($customerId, $companyId)
    {
        $company = $this->companyRepository->get((int) $companyId);
        $companyAdminId = $company->getSuperUserId();
        return ((int) $customerId) === ((int) $companyAdminId);
    }

    /**
     * Check if current user is manager.
     *
     * @param int $customerId
     * @return bool
     * @throws LocalizedException
     */
    private function isCurrentCustomerHasSubordinates($customerId)
    {
        return (bool) count($this->companyStructure->getAllowedChildrenIds($customerId));
    }
}
