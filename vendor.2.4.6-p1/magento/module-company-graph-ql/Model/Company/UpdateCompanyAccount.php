<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyManagement;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Update company data by authorized user.
 */
class UpdateCompanyAccount
{
    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var PrepareCompanyData
     */
    private $prepareCompanyData;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @param CompanyRepositoryInterface $companyRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param PrepareCompanyData $prepareCompanyData
     * @param CompanyManagement $companyManagement
     */
    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        DataObjectHelper $dataObjectHelper,
        PrepareCompanyData $prepareCompanyData,
        CompanyManagement $companyManagement
    ) {
        $this->companyRepository = $companyRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->prepareCompanyData = $prepareCompanyData;
        $this->companyManagement = $companyManagement;
    }

    /**
     * Update company account.
     *
     * @param array $data
     * @param int $customerId
     * @return CompanyInterface
     * @throws GraphQlInputException
     */
    public function execute(array $data, int $customerId): CompanyInterface
    {
        $company = $this->companyManagement->getByCustomerId($customerId);
        if (!$company) {
            throw new GraphQlInputException(
                __('No company assigned to this user found.')
            );
        }

        $companyData = $this->prepareCompanyData->execute($data);
        $this->dataObjectHelper->populateWithArray(
            $company,
            $companyData,
            CompanyInterface::class
        );
        try {
            $this->companyRepository->save($company);
        } catch (\Exception $e) {
            throw new GraphQlInputException(
                __($e->getMessage())
            );
        }

        return $company;
    }
}
