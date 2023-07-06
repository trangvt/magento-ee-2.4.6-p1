<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model;

use Exception;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\Data\RoleInterfaceFactory;
use Magento\Company\Api\Data\RoleSearchResultsInterfaceFactory;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\ResourceModel\Role\CollectionFactory;
use Magento\Company\Model\Role\Validator;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * A repository class for role entity that provides basic CRUD operations.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RoleRepository implements RoleRepositoryInterface
{
    /**
     * @var RoleInterface[]
     */
    private $instances = [];

    /**
     * @var RoleInterfaceFactory
     */
    private $roleFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Role
     */
    private $roleResource;

    /**
     * @var CollectionFactory
     */
    private $roleCollectionFactory;

    /**
     * @var RoleSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var \Magento\Company\Model\Role\Permission
     */
    private $rolePermission;

    /**
     * @var PermissionManagementInterface
     */
    private $permissionManagement;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param RoleInterfaceFactory $roleFactory
     * @param \Magento\Company\Model\ResourceModel\Role $roleResource
     * @param CollectionFactory $roleCollectionFactory
     * @param RoleSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\Company\Model\Role\Permission $rolePermission
     * @param PermissionManagementInterface $permissionManagement
     * @param Validator $validator
     *
     */
    public function __construct(
        RoleInterfaceFactory $roleFactory,
        \Magento\Company\Model\ResourceModel\Role $roleResource,
        CollectionFactory $roleCollectionFactory,
        RoleSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Company\Model\Role\Permission $rolePermission,
        PermissionManagementInterface $permissionManagement,
        Validator $validator
    ) {
        $this->roleFactory = $roleFactory;
        $this->roleResource = $roleResource;
        $this->roleCollectionFactory = $roleCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->rolePermission = $rolePermission;
        $this->permissionManagement = $permissionManagement;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function save(RoleInterface $role)
    {
        $role = $this->validator->retrieveRole($role);
        $allowedResources = $this->permissionManagement->retrieveAllowedResources($role->getPermissions());
        $permissions = $this->permissionManagement->populatePermissions($allowedResources);
        $this->validator->validatePermissions($permissions, $allowedResources);
        $role->setPermissions($permissions);
        if ((!$role->getId() || $role->getRoleName() !== $role->getOrigData(RoleInterface::ROLE_NAME))
            && $this->validateRoleName($role) === false
        ) {
            throw new CouldNotSaveException(__(
                'User role with this name already exists. Enter a different name to save this role.'
            ));
        }
        $this->roleResource->save($role);
        $this->rolePermission->saveRolePermissions($role);
        unset($this->instances[$role->getId()]);

        return $role;
    }

    /**
     * Validate that role name is unique.
     *
     * @param RoleInterface $role
     * @return bool
     */
    private function validateRoleName(RoleInterface $role)
    {
        $collection = $this->roleCollectionFactory->create();
        $collection->addFieldToFilter(
            RoleInterface::ROLE_NAME,
            ['eq' => $role->getRoleName()]
        );
        $collection->addFieldToFilter(
            RoleInterface::COMPANY_ID,
            ['eq' => $role->getCompanyId()]
        );

        if ($role->getId()) {
            $collection->addFieldToFilter(
                RoleInterface::ROLE_ID,
                ['neq' => $role->getId()]
            );
        }

        return !$collection->getSize();
    }

    /**
     * @inheritdoc
     */
    public function get($roleId)
    {
        if (!isset($this->instances[$roleId])) {
            /** @var RoleInterface $role */
            $role = $this->roleFactory->create();
            $this->roleResource->load($role, $roleId);
            $this->validator->checkRoleExist($role, $roleId);
            $role->setPermissions($this->rolePermission->getRolePermissions($role));
            $this->instances[$roleId] = $role;
        }
        return $this->instances[$roleId];
    }

    /**
     * @inheritdoc
     */
    public function delete($roleId)
    {
        $role = $this->get($roleId);
        $this->validator->validateRoleBeforeDelete($role);
        try {
            $this->roleResource->delete($role);
            $this->rolePermission->deleteRolePermissions($role);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(
                __(
                    'Cannot delete role with id %1',
                    $role->getId()
                ),
                $e
            );
        }
        unset($this->instances[$roleId]);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->roleCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $items = $collection->getItems();

        foreach ($items as $itemKey => $itemValue) {
            $items[$itemKey]->setPermissions($this->rolePermission->getRolePermissions($itemValue));
        }

        $searchResults->setItems($items);
        return $searchResults;
    }
}
