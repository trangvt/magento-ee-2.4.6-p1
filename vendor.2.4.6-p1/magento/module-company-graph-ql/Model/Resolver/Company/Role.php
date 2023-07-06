<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Resolver\Company;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\ResourceModel\UserRole\CollectionFactory;
use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\CompanyGraphQl\Model\Company\Role\PermissionsFormatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Provides customer company role data
 */
class Role implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    private $userRoleCollection;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var ResolverAccess
     */
    private $resolverAccess;

    /**
     * @var array
     */
    private $allowedResources;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var PermissionsFormatter
     */
    private $permissionsFormatter;

    /**
     * @param CollectionFactory $userRoleCollection
     * @param RoleRepositoryInterface $roleRepository
     * @param ResolverAccess $resolverAccess
     * @param Uid $idEncoder
     * @param PermissionsFormatter $permissionsFormatter
     * @param array $allowedResources
     */
    public function __construct(
        CollectionFactory $userRoleCollection,
        RoleRepositoryInterface $roleRepository,
        ResolverAccess $resolverAccess,
        Uid $idEncoder,
        PermissionsFormatter $permissionsFormatter,
        array $allowedResources = []
    ) {
        $this->userRoleCollection = $userRoleCollection;
        $this->roleRepository = $roleRepository;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
        $this->idEncoder = $idEncoder;
        $this->permissionsFormatter = $permissionsFormatter;
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
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Required parameter "id" is missing'));
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $company = $value['model'];
        $this->resolverAccess->isAllowed($this->allowedResources);

        try {
            $role = $this->roleRepository->get($this->idEncoder->decode($args['id']));
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Role with id "%role_id" does not exist.', ['role' => $args['id']]),
                $e
            );
        }

        if ((int)$role->getCompanyId() !== (int)$company->getId()) {
            return null;
        }

        return [
            'id' => $this->idEncoder->encode((string)$role->getId()),
            'name' => $role->getRoleName(),
            'users_count' => $this->userRoleCollection->create()
                ->addFieldToFilter('role_id', $role->getId())
                ->count(),
            'permissions' => $this->permissionsFormatter->format($role)
        ];
    }
}
