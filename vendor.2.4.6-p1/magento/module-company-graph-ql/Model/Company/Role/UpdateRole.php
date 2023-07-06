<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Role;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Company\Model\PermissionManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Update company role
 */
class UpdateRole
{
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
     * @param CompanyUser $companyUser
     * @param PermissionManagementInterface $permissionManagement
     * @param RoleRepositoryInterface $roleRepository
     * @param ValidateRole $validator
     */
    public function __construct(
        CompanyUser $companyUser,
        PermissionManagementInterface $permissionManagement,
        RoleRepositoryInterface $roleRepository,
        ValidateRole $validator
    ) {
        $this->companyUser = $companyUser;
        $this->permissionManagement = $permissionManagement;
        $this->roleRepository = $roleRepository;
        $this->validator = $validator;
    }

    /**
     * Update a role
     *
     * @param array $data
     * @return RoleInterface
     * @throws GraphQlInputException
     */
    public function execute(array $data)
    {
        $this->validator->addRequiredFields(['id']);
        $this->validator->execute($data);

        try {
            $companyId = $this->companyUser->getCurrentCompanyId();
            $role = $this->roleRepository->get($data['id']);
            if ($role->getCompanyId() !== $companyId) {
                throw new GraphQlInputException(__('Bad Request'));
            }
            $role->setCompanyId($companyId);
            if (isset($data['name'])) {
                $role->setRoleName($data['name']);
            }
            if (isset($data['permissions'])) {
                $role->setPermissions($this->permissionManagement->populatePermissions($data['permissions']));
            }

            $role = $this->roleRepository->save($role);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
        return $role;
    }
}
