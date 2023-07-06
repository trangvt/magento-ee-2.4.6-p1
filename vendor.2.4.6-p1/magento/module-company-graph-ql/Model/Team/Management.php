<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Team;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Manage the team
 */
class Management
{
    /**
     * @var TeamRepositoryInterface
     */
    private $teamRepository;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @param TeamRepositoryInterface $teamRepository
     * @param CompanyManagementInterface $companyManagement
     */
    public function __construct(
        TeamRepositoryInterface $teamRepository,
        CompanyManagementInterface $companyManagement
    ) {
        $this->teamRepository = $teamRepository;
        $this->companyManagement = $companyManagement;
    }

    /**
     * Loads team by the given identification
     *
     * @param int $teamId
     * @return TeamInterface
     * @throws NoSuchEntityException
     */
    public function loadTeam(int $teamId): TeamInterface
    {
        return $this->teamRepository->get($teamId);
    }

    /**
     * Save the team for the given customer
     *
     * @param TeamInterface $team
     * @param int $userId
     * @throws CouldNotSaveException
     */
    public function saveTeamForCustomer(TeamInterface $team, int $userId): void
    {
        $company = $this->getCompanyForCustomer($userId);
        $this->teamRepository->create($team, $company->getId());
    }

    /**
     * Get the company for the current user
     *
     * @param int $userId
     * @return CompanyInterface
     */
    private function getCompanyForCustomer(int $userId): CompanyInterface
    {
        return $this->companyManagement->getByCustomerId($userId);
    }

    /**
     * Update the given team
     *
     * @param int $teamId
     * @param TeamInterface $team
     * @throws CouldNotSaveException
     */
    public function update(int $teamId, TeamInterface $team): void
    {
        $team->setId($teamId);
        $this->teamRepository->save($team);
    }

    /**
     * Delete the given team
     *
     * @param TeamInterface $team
     * @throws LocalizedException
     */
    public function delete(TeamInterface $team): void
    {
        $this->teamRepository->delete($team);
    }
}
