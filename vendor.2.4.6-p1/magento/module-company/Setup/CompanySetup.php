<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Setup;

use Magento\Company\Model\Permission;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Company\Model\RoleRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Company setup handler.
 */
class CompanySetup
{
    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var PermissionManagementInterface
     */
    private $permissionManagement;

    /**
     * @var Permission[]
     */
    private $defaultPermissions;

    /**
     * @var int
     */
    private $pageSize = 150;

    /**
     * Recurring constructor.
     *
     * @param RoleRepository $roleRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PermissionManagementInterface $permissionManagement
     */
    public function __construct(
        RoleRepository $roleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PermissionManagementInterface $permissionManagement
    ) {
        $this->roleRepository = $roleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->permissionManagement = $permissionManagement;
    }

    /**
     * Initialize new permissions for roles in companies that exist by this moment.
     *
     * Apply allow/deny policy basing on \Magento\Company\Model\ResourcePool params that are set up in DI.
     *
     * @return void
     */
    public function applyPermissions(): void
    {
        $page = 1;
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setPageSize($this->pageSize);
        $rolesResults = $this->roleRepository->getList($searchCriteria);
        $totalCount = $rolesResults->getTotalCount();
        $pageCount = ceil($totalCount/$this->pageSize);
        do {
            $searchCriteria->setCurrentPage($page);
            $rolesResults = $this->roleRepository->getList($searchCriteria);
            foreach ($rolesResults->getItems() as $role) {
                try {
                    $this->updateUndefinedRolePermissions($role);
                    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                } catch (\Exception $exception) {
                    // do nothing for corrupted data.
                }
            }
            $page++;
        } while ($page <= $pageCount);
    }

    /**
     * Apply new permissions for the role.
     *
     * @param \Magento\Company\Api\Data\RoleInterface $role
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateUndefinedRolePermissions(\Magento\Company\Api\Data\RoleInterface $role): void
    {
        $rolePermissions = $role->getPermissions();
        $roleUndefinedDefaults = $this->getDefaultPermissions();
        foreach ($rolePermissions as $rolePermission) {
            foreach ($roleUndefinedDefaults as $key => $undefinedDefault) {
                if ($undefinedDefault->getResourceId() === $rolePermission->getResourceId()) {
                    unset($roleUndefinedDefaults[$key]);
                }
            }
        }
        if ($roleUndefinedDefaults) {
            $newPermissions = array_merge($rolePermissions, $roleUndefinedDefaults);
            $role->setPermissions($newPermissions);
            $this->roleRepository->save($role);
        }
    }

    /**
     * Load default permissions.
     *
     * @return \Magento\Company\Api\Data\PermissionInterface[]
     */
    private function getDefaultPermissions(): array
    {
        if (!$this->defaultPermissions) {
            $this->defaultPermissions = $this->permissionManagement->retrieveDefaultPermissions();
        }
        return $this->defaultPermissions;
    }
}
