<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\Role;

use Magento\Company\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;

/**
 * Class for managing role permissions.
 */
class Permission
{
    /**
     * @var \Magento\Company\Model\ResourceModel\Permission\CollectionFactory
     */
    private $permissionCollectionFactory;

    /**
     * @var \Magento\Framework\Acl\Data\CacheInterface
     */
    private $aclDataCache;

    /**
     * @var \Magento\Company\Api\AclInterface
     */
    private $userRoleManagement;

    /**
     * @param PermissionCollectionFactory $permissionCollectionFactory
     * @param \Magento\Framework\Acl\Data\CacheInterface $aclDataCache
     * @param \Magento\Company\Api\AclInterface $userRoleManagement
     */
    public function __construct(
        PermissionCollectionFactory $permissionCollectionFactory,
        \Magento\Framework\Acl\Data\CacheInterface $aclDataCache,
        \Magento\Company\Api\AclInterface $userRoleManagement
    ) {
        $this->permissionCollectionFactory = $permissionCollectionFactory;
        $this->aclDataCache = $aclDataCache;
        $this->userRoleManagement = $userRoleManagement;
    }

    /**
     * Gets a number of users assigned to the role.
     *
     * @param int $roleId
     * @return int
     */
    public function getRoleUsersCount($roleId)
    {
        return count($this->userRoleManagement->getUsersByRoleId($roleId));
    }

    /**
     * Get role permissions.
     *
     * @param \Magento\Company\Api\Data\RoleInterface $role
     * @return array
     */
    public function getRolePermissions(\Magento\Company\Api\Data\RoleInterface $role)
    {
        $permissionCollection = $this->permissionCollectionFactory->create();
        $permissionCollection->addFieldToFilter('role_id', ['eq' => $role->getId()])->load();
        return $permissionCollection->getItems();
    }

    /**
     * Delete role permissions.
     *
     * @param \Magento\Company\Api\Data\RoleInterface $role
     * @return void
     */
    public function deleteRolePermissions(\Magento\Company\Api\Data\RoleInterface $role)
    {
        $permissions = $this->getRolePermissions($role);
        foreach ($permissions as $permission) {
            $permission->delete();
        }
        $this->aclDataCache->clean();
    }

    /**
     * Save role permissions.
     *
     * @param \Magento\Company\Api\Data\RoleInterface $role
     * @return void
     */
    public function saveRolePermissions(\Magento\Company\Api\Data\RoleInterface $role)
    {
        $permissions = [];
        $existingPermissions = [];
        $permissionsToDelete = [];
        $permissionsToInsert = [];

        foreach ($this->getRolePermissions($role) as $permission) {
            $existingPermissions[$permission->getResourceId()] = $permission;
        }

        foreach ($role->getPermissions() as $permission) {
            $permissions[$permission->getResourceId()] = $permission;
            $existingPermission = $existingPermissions[$permission->getResourceId()] ?? null;
            if ($existingPermission === null) {
                $permissionsToInsert[] = $permission;
            } elseif ($existingPermission->getPermission() !== $permission->getPermission()) {
                $permissionsToInsert[] = $permission;
                $permissionsToDelete[] = $existingPermission;
            }
        }

        foreach ($existingPermissions as $existingPermission) {
            if (!isset($permissions[$existingPermission->getResourceId()])) {
                $permissionsToDelete[] = $existingPermission;
            }
        }

        foreach ($permissionsToDelete as $permission) {
            $permission->delete();
        }

        foreach ($permissionsToInsert as $permission) {
            $permission->setRoleId($role->getId());
            $permission->save();
        }
        $this->aclDataCache->clean();
    }
}
