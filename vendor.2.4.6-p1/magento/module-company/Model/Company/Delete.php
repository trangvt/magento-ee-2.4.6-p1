<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Model\Company;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Company\Model\StructureRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;

/**
 * Class for deleting a company entity.
 */
class Delete
{
    /**
     * @var int
     */
    private $noCompanyId = 0;

    /**
     * @var Company
     */
    private $companyResource;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Customer
     */
    private $customerResource;

    /**
     * @var Structure
     */
    private $structureManager;

    /**
     * @var TeamRepositoryInterface
     */
    private $teamRepository;

    /**
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @param Company $companyResource
     * @param Customer $customerResource
     * @param Structure $structureManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param TeamRepositoryInterface $teamRepository
     * @param StructureRepository $structureRepository
     */
    public function __construct(
        Company $companyResource,
        Customer $customerResource,
        Structure $structureManager,
        CustomerRepositoryInterface $customerRepository,
        TeamRepositoryInterface $teamRepository,
        StructureRepository $structureRepository
    ) {
        $this->companyResource = $companyResource;
        $this->customerResource = $customerResource;
        $this->structureManager = $structureManager;
        $this->customerRepository = $customerRepository;
        $this->teamRepository = $teamRepository;
        $this->structureRepository = $structureRepository;
    }

    /**
     * Detaches customer entities from company entity and deletes it.
     *
     * @param CompanyInterface $company
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function delete(CompanyInterface $company)
    {
        $allowedIds = $this->structureManager->getAllowedIds($company->getSuperUserId());
        $teams = $this->structureManager->getUserChildTeams($company->getSuperUserId());
        $this->companyResource->delete($company);
        $this->detachCustomersFromCompany($allowedIds['users']);
        $this->deleteTeams($teams);
    }

    /**
     * Delete company teams.
     *
     * @param StructureInterface[] $teams
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function deleteTeams(array $teams)
    {
        foreach ($teams as $teamStructure) {
            $this->teamRepository->deleteById($teamStructure->getEntityId());
            $this->structureRepository->delete($teamStructure);
        }
    }

    /**
     * Detach customers from the company.
     *
     * @param array $users
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    private function detachCustomersFromCompany(array $users)
    {
        foreach ($users as $customerId) {
            $this->structureManager->removeCustomerNode($customerId);
            $this->detachCustomerFromCompany($customerId);
        }
    }

    /**
     * Detach the customer from the company.
     *
     * @param int $customerId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    private function detachCustomerFromCompany($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        /** @var CompanyCustomerInterface $companyAttributes */
        $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        $companyAttributes->setCompanyId($this->noCompanyId);
        $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_INACTIVE);
        $this->customerRepository->save($customer);
    }
}
