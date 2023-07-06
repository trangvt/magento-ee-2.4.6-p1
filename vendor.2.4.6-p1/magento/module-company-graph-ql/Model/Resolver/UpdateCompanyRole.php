<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Model\Role\Permission;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Role\PermissionsFormatter;
use Magento\CompanyGraphQl\Model\Company\Role\UpdateRole;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Update company role resolver.
 */
class UpdateCompanyRole implements ResolverInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * @var UpdateRole
     */
    private $updateRole;

    /**
     * @var Permission
     */
    private $rolePermission;

    /**
     * @var PermissionsFormatter
     */
    private $permissionsFormatter;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var array
     */
    private $allowedResources = [self::COMPANY_RESOURCE];

    /**
     * @param UpdateRole $updateRole
     * @param Permission $permission
     * @param PermissionsFormatter $permissionsFormatter
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     */
    public function __construct(
        UpdateRole $updateRole,
        Permission $permission,
        PermissionsFormatter $permissionsFormatter,
        ResolverAccess $resolverAccess,
        Uid $idEncoder
    ) {
        $this->updateRole = $updateRole;
        $this->rolePermission = $permission;
        $this->permissionsFormatter = $permissionsFormatter;
        $this->resolverAccess = $resolverAccess;
        $this->idEncoder = $idEncoder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($this->allowedResources);

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $args['input']['id'] = $this->idEncoder->decode($args['input']['id']);

        /** @var ContextInterface $context */
        $role = $this->updateRole->execute($args['input']);

        return [
            'role' => [
                'id' => $this->idEncoder->encode((string)$role->getId()),
                'name' => $role->getRoleName(),
                'permissions' => $this->permissionsFormatter->format($role),
                'users_count' => $this->rolePermission->getRoleUsersCount($role->getId())
            ]
        ];
    }
}
