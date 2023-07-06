<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Role;

use Magento\Company\Api\Data\PermissionInterface;
use Magento\Framework\Acl\AclResource\ProviderInterface;

/**
 * Transform company role data to tree format
 */
class Permissions
{
    /**
     * @var ProviderInterface
     */
    private $resourceProvider;

    /**
     * @param ProviderInterface $resourceProvider
     */
    public function __construct(
        ProviderInterface $resourceProvider
    ) {
        $this->resourceProvider = $resourceProvider;
    }

    /**
     * Get allowed company resources
     *
     * @param array $permissions
     * @return array
     */
    public function getRolePermissionTree(array $permissions = []): array
    {
        $resources = $this->resourceProvider->getAclResources();
        return $this->prepareTreeData($resources, $permissions);
    }

    /**
     * Return company allowed role permissions
     *
     * @param array $resources
     * @param array $permissions
     * @return array
     */
    private function prepareTreeData(array $resources, array $permissions): array
    {
        foreach ($resources as $counter => $resource) {
            $resources[$counter]['text'] = $resource['title'] ?? null;
            unset($resources[$counter]['title']);
            $resources[$counter]['sort_order'] = $resource['sortOrder'] ?? null;
            unset($resources[$counter]['sortOrder']);
            if (!empty($resource['children'])) {
                $resources[$counter]['children'] = $this->prepareTreeData(
                    $resources[$counter]['children'],
                    $permissions
                );
            }
            if (isset($permissions[$resource['id']])
                && $permissions[$resources[$counter]['id']] === PermissionInterface::DENY_PERMISSION) {
                unset($resources[$counter]);
            }
        }
        return $resources;
    }

    /**
     * Remove denied permissions from the tree
     *
     * @param array $permissionTree
     * @param array $allowedPermissions
     * @return array
     */
    public function removeRedundantPermissions(array $permissionTree, array $allowedPermissions): array
    {
        foreach ($permissionTree as $counter => $resource) {
            if (!empty($resource['children'])) {
                $result = $this->removeRedundantPermissions($permissionTree[$counter]['children'], $allowedPermissions);
            }
            if (empty($result) && !in_array($resource['id'], $allowedPermissions, true)) {
                unset($permissionTree[$counter]);
            } else {
                foreach (array_keys($resource['children']) as $index) {
                    if (isset($result[$index])) {
                        $permissionTree[$counter]['children'][$index] = $result[$index];
                    } else {
                        unset($permissionTree[$counter]['children'][$index]);
                    }
                }
            }
        }
        return $permissionTree;
    }
}
