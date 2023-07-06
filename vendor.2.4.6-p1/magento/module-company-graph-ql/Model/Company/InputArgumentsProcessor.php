<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\Framework\Exception\LocalizedException;

/**
 * Process Request arguments
 */
class InputArgumentsProcessor
{
    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @param CompanyStructure $companyStructure
     */
    public function __construct(
        CompanyStructure $companyStructure
    ) {
        $this->companyStructure = $companyStructure;
    }

    /**
     * Extract the team id from request arguments
     *
     * @param array $requestArguments
     * @return int
     */
    public function extractTeamId(array $requestArguments): int
    {
        return (int) ($requestArguments['id'] ?? ($requestArguments['input']['id'] ?? 0));
    }

    /**
     * Extract the raw create data from request arguments
     *
     * @param array $requestArguments
     * @return array
     */
    public function extractCreateData(array $requestArguments): array
    {
        return [
            'name' => $requestArguments['input']['name'],
            'description' => $requestArguments['input']['description'] ?? ''
        ];
    }

    /**
     * Extract the raw data from request arguments. Fill whatever is omitted using the previously saved data.
     *
     * @param array $requestArguments
     * @param TeamInterface $loadedTeam
     * @return array
     */
    public function extractUpdateData(array $requestArguments, TeamInterface $loadedTeam): array
    {
        return [
            'name' => $requestArguments['input']['name'] ?? $loadedTeam->getName(),
            'description' => $requestArguments['input']['description'] ?? $loadedTeam->getDescription()
        ];
    }

    /**
     * Get the ID of the parent of the team
     *
     * It is either explicitly requested, or will default to the company administrator root structure id
     *
     * @param array $requestArguments
     * @param int $userId
     * @return int
     * @throws LocalizedException
     */
    public function getTargetId(array $requestArguments, int $userId): int
    {
        return (int) ($requestArguments['input']['target_id'] ?? $this->getRootStructureIdForCustomer($userId));
    }

    /**
     * Get the ID of the default parent in the structure of teams
     *
     * @param int $userId
     * @return int
     * @throws LocalizedException
     */
    private function getRootStructureIdForCustomer(int $userId): int
    {
        $structure = $this->companyStructure->getStructureByCustomerId($userId);

        if ($structure) {
            return (int)$structure->getStructureId();
        }

        return 0;
    }
}
