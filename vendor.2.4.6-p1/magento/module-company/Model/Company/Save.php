<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model\Company;

use Magento\Company\Model\SaveHandlerPool;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\SaveValidatorPool;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class responsible for creating and updating company entities.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save
{
    /**
     * @var SaveHandlerPool
     */
    private SaveHandlerPool $saveHandlerPool;

    /**
     * @var Company
     */
    private Company $companyResource;

    /**
     * @var CompanyInterfaceFactory
     */
    private CompanyInterfaceFactory $companyFactory;

    /**
     * @var SaveValidatorPool
     */
    private SaveValidatorPool $saveValidatorPool;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $userCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SaveHandlerPool $saveHandlerPool
     * @param Company $companyResource
     * @param CompanyInterfaceFactory $companyFactory
     * @param SaveValidatorPool $saveValidatorPool
     * @param CollectionFactory $userCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        SaveHandlerPool $saveHandlerPool,
        Company $companyResource,
        CompanyInterfaceFactory $companyFactory,
        SaveValidatorPool $saveValidatorPool,
        CollectionFactory $userCollectionFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->saveHandlerPool = $saveHandlerPool;
        $this->companyResource = $companyResource;
        $this->companyFactory = $companyFactory;
        $this->saveValidatorPool = $saveValidatorPool;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Checks if provided data is correct, saves the company entity and executes additional save handlers from the pool.
     *
     * @param CompanyInterface $company
     * @return CompanyInterface
     * @throws CouldNotSaveException
     */
    public function save(CompanyInterface $company)
    {
        $this->processAddress($company);
        $this->processSalesRepresentative($company);
        $companyId = $company->getId();
        $initialCompany = $this->getInitialCompany($companyId);
        try {
            $this->saveValidatorPool->execute($company, $initialCompany);
            $this->companyResource->save($company);
            $this->saveHandlerPool->execute($company, $initialCompany);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CouldNotSaveException(__('Could not save company'));
        }
        return $company;
    }

    /**
     * Get initial company.
     *
     * @param int|null $companyId
     * @return CompanyInterface
     */
    private function getInitialCompany($companyId)
    {
        $company = $this->companyFactory->create();
        try {
            $this->companyResource->load($company, $companyId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $company;
    }

    /**
     * Set default sales representative (admin user responsible for company) if it is not set.
     *
     * @param CompanyInterface $company
     * @return void
     */
    private function processSalesRepresentative(CompanyInterface $company)
    {
        if (!$company->getSalesRepresentativeId()) {
            /** @var \Magento\User\Model\ResourceModel\User\Collection $userCollection */
            $userCollection = $this->userCollectionFactory->create();
            $company->setSalesRepresentativeId($userCollection->setPageSize(1)->getFirstItem()->getId());
        }
    }

    /**
     * Prepare company address.
     *
     * @param CompanyInterface $company
     * @return void
     */
    private function processAddress(CompanyInterface $company)
    {
        if (!$company->getRegionId()) {
            $company->setRegionId(null);
        } else {
            $company->setRegion(null);
        }
        $street = $company->getStreet();
        if (is_array($street) && count($street)) {
            $company->setStreet(trim(implode("\n", $street)));
        }
    }
}
