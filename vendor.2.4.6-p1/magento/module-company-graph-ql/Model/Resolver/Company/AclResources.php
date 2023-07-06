<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Role\Permissions;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Provides customer associated company ACL resources
 */
class AclResources implements ResolverInterface
{
    /**
     * @var Permissions
     */
    private $permissions;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @param Permissions $permissions
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        Permissions $permissions,
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->permissions = $permissions;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $this->resolverAccess->isAllowed($this->allowedResources);
        return $this->permissions->getRolePermissionTree();
    }
}
