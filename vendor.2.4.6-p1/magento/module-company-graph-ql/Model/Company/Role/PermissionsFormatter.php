<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Role;

use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\RoleInterface;

/**
 * Format permissions for role
 */
class PermissionsFormatter
{
    /**
     * @var array
     */
    private $resources = [];

    /**
     * @var array
     */
    private $allowedResources = [];

    /**
     * @var Permissions
     */
    private $permissions;

    /**
     * @param Permissions $permissions
     */
    public function __construct(Permissions $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Format permissions for role
     *
     * @param RoleInterface $role
     * @return array
     */
    public function format(RoleInterface $role)
    {
        $this->preparePermissions($role);
        $tree = $this->permissions->getRolePermissionTree($this->resources);
        $tree = $this->permissions->removeRedundantPermissions($tree, $this->allowedResources);
        $this->resetPermissions();
        return $tree;
    }

    /**
     * Init role's permissions
     *
     * @param RoleInterface $role
     */
    private function preparePermissions(RoleInterface $role)
    {
        foreach ($role->getPermissions() as $permission) {
            $this->resources[$permission->getResourceId()] = $permission->getPermission();
            if ($permission->getPermission() === PermissionInterface::ALLOW_PERMISSION) {
                $this->allowedResources[] = $permission->getResourceId();
            }
        }
    }

    /**
     * Reset permissions
     */
    private function resetPermissions()
    {
        $this->resources = [];
        $this->allowedResources = [];
    }
}
