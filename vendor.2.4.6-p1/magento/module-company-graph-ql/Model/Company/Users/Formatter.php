<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Users;

use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\UserRoleManagement;
use Magento\CompanyGraphQl\Model\Company\Role\PermissionsFormatter;
use Magento\CompanyGraphQl\Model\Company\Users;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Prepare output for company user mutation resolvers
 */
class Formatter
{
    /**
     * @var ExtractCustomerData
     */
    private $customerData;

    /**
     * @var UserRoleManagement
     */
    private $userRoleManagement;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var PermissionsFormatter
     */
    private $permissionsFormatter;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @param ExtractCustomerData $customerData
     * @param UserRoleManagement $userRoleManagement
     * @param Structure $structure
     * @param PermissionsFormatter $permissionsFormatter
     * @param RoleRepositoryInterface $roleRepository
     * @param Uid $idEncoder
     */
    public function __construct(
        ExtractCustomerData $customerData,
        UserRoleManagement $userRoleManagement,
        Structure $structure,
        PermissionsFormatter $permissionsFormatter,
        RoleRepositoryInterface $roleRepository,
        Uid $idEncoder
    ) {
        $this->customerData = $customerData;
        $this->userRoleManagement = $userRoleManagement;
        $this->permissionsFormatter = $permissionsFormatter;
        $this->structure = $structure;
        $this->roleRepository = $roleRepository;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Format user according to the GraphQL schema
     *
     * @param CustomerInterface $user
     * @return array
     * @throws LocalizedException
     */
    public function formatUser(CustomerInterface $user): array
    {
        $customerData = $this->customerData->execute($user);
        $companyAttributes = $user->getExtensionAttributes()->getCompanyAttributes();
        if ($companyAttributes) {
            $customerData['job_title'] = $companyAttributes->getJobTitle();
            $customerData['telephone'] = $companyAttributes->getTelephone();
            $customerData['status'] = $this->formatStatusFromDb((int)$companyAttributes->getStatus());
        }
        $customerData['structure_id'] = $this->idEncoder->encode(
            (string) $this->structure->getStructureByCustomerId($user->getId())->getId()
        );

        $customerData['role'] = $this->formatRole((int)$user->getId());
        $customerData['team'] = $this->formatTeam((int)$user->getId());

        return $customerData;
    }

    /**
     * Format user's role according to the GraphQL schema
     *
     * @param int $userId
     * @return array
     */
    public function formatRole(int $userId): ?array
    {
        $roles = $this->userRoleManagement->getRolesByUserId($userId);
        if (!$roles) {
            return null;
        }

        $role = current($roles);

        try {
            $role = $this->roleRepository->get($role->getId());
            return [
                'id' => $this->idEncoder->encode((string)$role->getId()),
                'name' => $role->getRoleName(),
                'users_count' => count($this->userRoleManagement->getUsersByRoleId($role->getId())),
                'permissions' => $this->permissionsFormatter->format($role)
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert user's status from db value to CompanyUserStatusEnum value
     *
     * @param int $status
     * @return string
     */
    public function formatStatusFromDb(int $status): string
    {
        return $status ? Users::STATUS_ACTIVE : Users::STATUS_INACTIVE;
    }

    /**
     * Convert user's status from CompanyUserStatusEnum to db value
     *
     * @param string $enumValue
     * @return int
     */
    public function formatStatusFromEnum(string $enumValue): int
    {
        return (int)($enumValue === Users::STATUS_ACTIVE);
    }

    /**
     * Format user's team according to the GraphQL schema
     *
     * @param int $userId
     * @return array|null
     */
    public function formatTeam(int $userId): ?array
    {
        try {
            $team = $this->structure->getTeamByCustomerId($userId);
        } catch (LocalizedException $e) {
            return null;
        }

        if ($team instanceof TeamInterface) {
            return [
                'id' => $this->idEncoder->encode((string)$team->getId()),
                'name' => $team->getName(),
                'description' => $team->getDescription(),
                'structure_id' => $this->idEncoder->encode(
                    (string) $this->structure->getStructureByTeamId($team->getId())->getId()
                )
            ];
        }

        return null;
    }
}
