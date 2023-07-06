<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver;

use Magento\Company\Model\Role\Permission;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Role\CreateRole;
use Magento\CompanyGraphQl\Model\Company\Role\PermissionsFormatter;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Create company role resolver.
 */
class CreateCompanyRole implements ResolverInterface
{
    /**
     * Authorization level of a company session.
     */
    public const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * @var CreateRole
     */
    private $createCompanyRole;

    /**
     * @var Permission
     */
    private $rolePermission;

    /**
     * @var PermissionsFormatter
     */
    private $permissionsFormatter;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources = [self::COMPANY_RESOURCE];

    /**
     * @param CreateRole $createCompanyRole
     * @param Permission $permission
     * @param PermissionsFormatter $permissionsFormatter
     * @param Uid $idEncoder
     * @param ResolverAccess $resolverAccess
     */
    public function __construct(
        CreateRole $createCompanyRole,
        Permission $permission,
        PermissionsFormatter $permissionsFormatter,
        Uid $idEncoder,
        ResolverAccess $resolverAccess
    ) {
        $this->createCompanyRole = $createCompanyRole;
        $this->rolePermission = $permission;
        $this->permissionsFormatter = $permissionsFormatter;
        $this->idEncoder = $idEncoder;
        $this->resolverAccess = $resolverAccess;
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

        $role = $this->createCompanyRole->execute($args['input']);

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
