<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Role;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\Data\RoleInterfaceFactory;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Create company role
 */
class CreateRole
{
    /**
     * @var RoleInterfaceFactory
     */
    private $roleFactory;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @var PermissionManagementInterface
     */
    private $permissionManagement;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var ValidateRole
     */
    private $validator;

    /**
     * @param RoleInterfaceFactory $roleFactory
     * @param CompanyUser $companyUser
     * @param PermissionManagementInterface $permissionManagement
     * @param RoleRepositoryInterface $roleRepository
     * @param ValidateRole $validator
     */
    public function __construct(
        RoleInterfaceFactory $roleFactory,
        CompanyUser $companyUser,
        PermissionManagementInterface $permissionManagement,
        RoleRepositoryInterface $roleRepository,
        ValidateRole $validator
    ) {
        $this->roleFactory = $roleFactory;
        $this->companyUser = $companyUser;
        $this->permissionManagement = $permissionManagement;
        $this->roleRepository = $roleRepository;
        $this->validator = $validator;
    }

    /**
     * Create a role
     *
     * @param array $data
     * @return RoleInterface
     * @throws GraphQlInputException
     */
    public function execute(array $data)
    {
        $this->validator->addRequiredFields(['name', 'permissions']);
        $this->validator->execute($data);

        $role = $this->roleFactory->create();

        try {
            $companyId = $this->companyUser->getCurrentCompanyId();
            $role->setRoleName($data['name']);
            $role->setCompanyId($companyId);
            $role->setPermissions($this->permissionManagement->populatePermissions($data['permissions']));
            $role = $this->roleRepository->save($role);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
        return $role;
    }
}
