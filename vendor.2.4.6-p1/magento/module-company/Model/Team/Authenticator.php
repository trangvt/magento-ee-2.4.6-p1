<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Model\Team;

use Magento\Company\Model\Company\Structure;

/**
 * Utility class that answers what a given user is allowed in relation to a given team
 */
class Authenticator
{
    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @param Structure $structureManager
     */
    public function __construct(
        Structure $structureManager
    ) {
        $this->structureManager = $structureManager;
    }

    /**
     * Check if the given user can create a team as a child of the specified parent
     *
     * @param int $userId
     * @param int $parentStructureId
     * @return bool
     */
    public function isUserAllowedToCreateTeam(int $userId, int $parentStructureId): bool
    {
        if (!in_array($parentStructureId, $this->allowedStructures($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given user can update the given team
     *
     * @param int $userId
     * @param int $teamId
     * @return bool
     */
    public function isAllowedToUpdateTeam(int $userId, int $teamId): bool
    {
        if (!in_array($teamId, $this->allowedTeams($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given user can delete the given team
     *
     * @param int $userId
     * @param int $teamId
     * @return bool
     */
    public function isAllowedToDeleteTeam(int $userId, int $teamId): bool
    {
        if (!in_array($teamId, $this->allowedTeams($userId))) {
            return false;
        }

        return true;
    }

    /**
     * Get allowed structures for a given user
     *
     * @param int $userId
     * @return array
     */
    private function allowedStructures(int $userId): array
    {
        return $this->getPermissions($userId)['structures'];
    }

    /**
     * Get allowed teams for a given user
     *
     * @param int $userId
     * @return array
     */
    private function allowedTeams(int $userId): array
    {
        return $this->getPermissions($userId)['teams'];
    }

    /**
     * Load allowed ids of structures, teams and users
     *
     * @param int $userId
     * @return array
     */
    private function getPermissions(int $userId): array
    {
        return $this->structureManager->getAllowedIds($userId);
    }
}
