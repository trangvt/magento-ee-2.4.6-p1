<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Api;

/**
 * Interface for managing company roles.
 *
 * @api
 * @since 100.0.0
 */
interface RoleManagementInterface
{
    /**
     * Get roles by company id.
     *
     * @param int $companyId
     * @param bool $includeAdminRole [optional]
     * @return \Magento\Company\Api\Data\RoleInterface[]
     */
    public function getRolesByCompanyId($companyId, $includeAdminRole = true);

    /**
     * Get admin role.
     *
     * @return \Magento\Company\Api\Data\RoleInterface
     */
    public function getAdminRole();

    /**
     * Get manager role.
     *
     * @return \Magento\Company\Api\Data\RoleInterface
     * @since 100.2.0
     */
    public function getManagerRole();

    /**
     * Get company default role.
     *
     * @param int $companyId
     * @return \Magento\Company\Api\Data\RoleInterface
     */
    public function getCompanyDefaultRole($companyId);
}
