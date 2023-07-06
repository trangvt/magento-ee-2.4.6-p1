<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Model\ResourceModel\UserRole\CollectionFactory;
use Magento\CompanyGraphQl\Model\Company\Role\PermissionsFormatter;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Retrieve the role data as array
 */
class GetRoleData
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $userRoleCollection;

    /**
     * @var Uid
     */
    private Uid $idEncoder;

    /**
     * @var PermissionsFormatter
     */
    private PermissionsFormatter $permissionsFormatter;

    /**
     * @param CollectionFactory $userRoleCollection
     * @param Uid $idEncoder
     * @param PermissionsFormatter $permissionsFormatter
     */
    public function __construct(
        CollectionFactory $userRoleCollection,
        Uid $idEncoder,
        PermissionsFormatter $permissionsFormatter
    ) {
        $this->userRoleCollection = $userRoleCollection;
        $this->idEncoder = $idEncoder;
        $this->permissionsFormatter = $permissionsFormatter;
    }

    /**
     * Get role data formatted for GraphQL response
     *
     * @param RoleInterface $role
     * @return array
     */
    public function execute(RoleInterface $role): array
    {
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
