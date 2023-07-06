<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\Authorization\Loader;

use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\Permission;
use Magento\Framework\Acl;
use Magento\Framework\Acl\RootResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Company\Model\ResourceModel\Permission\Collection as PermissionCollection;
use Magento\Framework\Acl\LoaderInterface as AclLoaderInterface;
use Magento\Framework\Acl\AclResource\ProviderInterface as AclProviderInterface;

/**
 * Populate ACL permissions for all company roles.
 */
class Rule implements AclLoaderInterface
{
    /**
     * @var PermissionCollection
     */
    private $collection;

    /**
     * @var RootResource
     */
    private $rootResource;

    /**
     * @var AclProviderInterface
     */
    private $resourceProvider;

    /**
     * @var RoleManagementInterface
     */
    private $roleManagement;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @param RootResource $rootResource
     * @param PermissionCollection $collection
     * @param AclProviderInterface $resourceProvider
     * @param RoleManagementInterface $roleManagement
     * @param CompanyUser $companyUser
     */
    public function __construct(
        RootResource $rootResource,
        PermissionCollection $collection,
        AclProviderInterface $resourceProvider,
        RoleManagementInterface $roleManagement,
        CompanyUser $companyUser
    ) {
        $this->rootResource = $rootResource;
        $this->collection = $collection;
        $this->resourceProvider = $resourceProvider;
        $this->roleManagement = $roleManagement;
        $this->companyUser = $companyUser;
    }

    /**
     * Populate ACL with rules from external storage.
     *
     * @param Acl $acl
     * @return void
     * @throws LocalizedException|NoSuchEntityException
     */
    public function populateAcl(Acl $acl)
    {
        $resourceTree = $this->resourceProvider->getAclResources();
        $processedResources = [];
        $this->collection->addFieldToFilter(
            PermissionInterface::ROLE_ID,
            ['in' => $this->getCompanyRolesIds()]
        );
        $permissions = $this->collection->getItems();

        // Sort resources - children must be processed after parent
        $aclArray = [];
        array_walk_recursive($resourceTree, function ($value, $key) use (&$aclArray) {
            if ($key === 'id') {
                $aclArray[] = $value['id'] ?? $value;
            }
        }, $aclArray);
        $aclArray = array_flip($aclArray);
        usort($permissions, function ($firstItem, $secondItem) use ($aclArray) {
            return $aclArray[$firstItem->getResourceId()] > $aclArray[$secondItem->getResourceId()] ? 1 : -1;
        });

        foreach ($permissions as $rule) {
            /** @var PermissionInterface $rule */
            $roleId = $rule->getRoleId();
            $resource = $rule->getResourceId();

            if (!isset($processedResources[$roleId])) {
                $processedResources[$roleId] = [];
            }
            $this->hydrateAclByResource($acl, $rule, $resource, $roleId, $processedResources);
        }
    }

    /**
     * Hydrate Acl with rules only if Acl has each resource
     *
     * @param Acl $acl
     * @param Permission $rule
     * @param string $resource
     * @param int $roleId
     * @param array $processedResources
     * @return void
     */
    private function hydrateAclByResource(
        Acl $acl,
        Permission $rule,
        $resource,
        $roleId,
        array &$processedResources
    ): void {
        if ($acl->hasResource($resource) && !in_array($resource, $processedResources[$roleId])) {
            if ($rule->getPermission() === 'allow') {
                if ($resource === $this->rootResource->getId()) {
                    $acl->allow($roleId);
                }
                $acl->allow($roleId, $resource);
                $processedResources[$roleId][] = $resource;
            } elseif ($rule->getPermission() === 'deny') {
                $acl->deny($roleId, $resource);
                $processedResources[$roleId][] = $resource;
            }
        }
    }

    /**
     * Get IDs of all company roles.
     *
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    private function getCompanyRolesIds(): array
    {
        $roles = $this->roleManagement->getRolesByCompanyId($this->companyUser->getCurrentCompanyId());
        return array_map(
            function ($role) {
                return (int)$role->getId();
            },
            $roles
        );
    }
}
