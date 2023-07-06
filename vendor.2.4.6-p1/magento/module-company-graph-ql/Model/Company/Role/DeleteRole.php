<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Role;

use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Framework\Exception\LocalizedException;

/**
 *  Delete company role
 */
class DeleteRole
{
    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * @param RoleRepositoryInterface $roleRepository
     * @param CompanyUser $companyUser
     */
    public function __construct(
        RoleRepositoryInterface $roleRepository,
        CompanyUser $companyUser
    ) {
        $this->roleRepository = $roleRepository;
        $this->companyUser = $companyUser;
    }

    /**
     * Delete a role
     *
     * @param int $id
     * @return bool
     */
    public function execute(int $id): bool
    {
        try {
            $role = $this->roleRepository->get($id);
            $companyId = $this->companyUser->getCurrentCompanyId();

            if ($role->getCompanyId() != $companyId) {
                throw new LocalizedException(__('Bad Request'));
            }

            $this->roleRepository->delete($role->getId());
            return true;
        } catch (LocalizedException $e) {
            return false;
        }
    }
}
