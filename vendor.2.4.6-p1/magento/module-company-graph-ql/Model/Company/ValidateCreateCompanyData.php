<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Company\Model\SaveValidator\CompanyAddress;
use Magento\Company\Model\SaveValidator\CompanyAddressFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\ValidateCustomerData\ValidateGender;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Validator\EmailAddress as EmailAddressValidator;

/**
 * Input validation of company
 */
class ValidateCreateCompanyData
{
    /**
     * @var CompanyInterfaceFactory
     */
    private $companyFactory;

    /**
     * @var EmailAddressValidator
     */
    private $emailAddressValidator;

    /**
     * @var CompanyAddressFactory
     */
    private $companyAddressValidatorFactory;

    /**
     * @var InputException
     */
    private $inputException;

    /**
     * @var PrepareCompanyData
     */
    private $prepareCompanyData;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CollectionFactory
     */
    private $companyCollectionFactory;

    /**
     * @var ValidateGender
     */
    private $validateGender;

    /**
     * @param CompanyInterfaceFactory $companyFactory
     * @param EmailAddressValidator $emailAddressValidator
     * @param CompanyAddressFactory $companyAddressValidatorFactory
     * @param PrepareCompanyData $prepareCompanyData
     * @param InputException $inputException
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param CollectionFactory $companyCollectionFactory
     * @param ValidateGender $validateGender
     */
    public function __construct(
        CompanyInterfaceFactory $companyFactory,
        EmailAddressValidator $emailAddressValidator,
        CompanyAddressFactory $companyAddressValidatorFactory,
        PrepareCompanyData $prepareCompanyData,
        InputException $inputException,
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        CollectionFactory $companyCollectionFactory,
        ValidateGender $validateGender
    ) {
        $this->companyFactory = $companyFactory;
        $this->emailAddressValidator = $emailAddressValidator;
        $this->companyAddressValidatorFactory = $companyAddressValidatorFactory;
        $this->prepareCompanyData = $prepareCompanyData;
        $this->inputException = $inputException;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
        $this->companyCollectionFactory = $companyCollectionFactory;
        $this->validateGender = $validateGender;
    }

    /**
     * Execute company data validation.
     *
     * @param array $companyData
     * @throws GraphQlInputException
     */
    public function execute(array $companyData): void
    {
        $companyDataObject = $this->companyFactory->create([
            'data' => $this->prepareCompanyData->execute($companyData)
        ]);
        $this->validateEmail($companyData);
        $this->validateAddress($companyDataObject);

        $this->validateGender->execute($companyData['company_admin']);
    }

    /**
     * Validate address of company.
     *
     * @param CompanyInterface $company
     * @throws GraphQlInputException
     */
    private function validateAddress($company): void
    {
        /** @var CompanyAddress $companyAddressValidator */
        $companyAddressValidator = $this->companyAddressValidatorFactory->create([
            'company' => $company,
            'exception' => $this->inputException
        ]);

        $companyAddressValidator->execute();
        if ($this->inputException->wasErrorAdded()) {
            throw new GraphQlInputException(__($this->inputException->getMessage()));
        }
    }

    /**
     * Validate email value.
     *
     * @param array $companyData
     * @throws GraphQlInputException
     */
    private function validateEmail($companyData): void
    {
        if (isset($companyData['company_email'])
            && !$this->emailAddressValidator->isValid($companyData['company_email'])
        ) {
            throw new GraphQlInputException(
                __('"%1" is not a valid email address.', $companyData['company_email'])
            );
        }

        if (isset($companyData['company_admin']['email'])
            && !$this->emailAddressValidator->isValid($companyData['company_admin']['email'])
        ) {
            throw new GraphQlInputException(
                __('"%1" is not a valid email address.', $companyData['company_admin']['email'])
            );
        }

        try {
            $customer = $this->customerRepository->get($companyData['company_admin']['email']);
        } catch (NoSuchEntityException $e) {
            $customer = null;
        }
        if ($customer && $customer->getId()) {
            throw new GraphQlInputException(
                __('A customer with the same email address already exists in an associated website.')
            );
        }

        $companyCollection = $this->companyCollectionFactory->create();
        $companyCollection->addFieldToFilter(
            'company_email',
            $companyData['company_email']
        )->load();

        if ($companyCollection->getSize() > 0) {
            throw new GraphQlInputException(
                __('Company with this email address already exists in the system.'
                    . 'Enter a different email address to continue.')
            );
        }
    }
}
