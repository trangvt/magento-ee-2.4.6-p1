<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\Authorization;

use Magento\Authorization\Model\UserContextInterface;

/**
 * Company role locator.
 */
class RoleLocator implements \Magento\Framework\Authorization\RoleLocatorInterface
{
    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Company\Api\AclInterface
     */
    private $roleManagement;

    /**
     * @param \Magento\Company\Model\CompanyAdminPermission
     */
    private $adminPermission;

    /**
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Company\Api\AclInterface $roleManagement
     * @param \Magento\Company\Model\CompanyAdminPermission $adminPermission
     */
    public function __construct(
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Company\Api\AclInterface $roleManagement,
        \Magento\Company\Model\CompanyAdminPermission $adminPermission
    ) {
        $this->userContext = $userContext;
        $this->roleManagement = $roleManagement;
        $this->adminPermission = $adminPermission;
    }

    /**
     * Retrieve current role.
     *
     * @return string|null
     */
    public function getAclRoleId()
    {
        $roleId = null;
        $userId = $this->userContext->getUserId();
        $userType = $this->userContext->getUserType();
        if ($userId && $userType == UserContextInterface::USER_TYPE_CUSTOMER) {
            $roles = $this->roleManagement->getRolesByUserId($userId);
            if (!empty($roles)) {
                $role = array_shift($roles);
                $roleId = $role->getData('role_id');
            } elseif ($this->adminPermission->isGivenUserCompanyAdmin($userId)) {
                $roleId = 0;
            }
        }
        return $roleId;
    }
}
