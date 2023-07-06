<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company;

use Magento\Backend\Model\UrlInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Customer\Company;
use Magento\Company\Model\Email\Sender;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Create company with admin account.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateCompanyAccount
{
    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var ValidateCreateCompanyData
     */
    private $validateCompanyData;

    /**
     * @var Company
     */
    private $customerCompany;

    /**
     * @var Sender
     */
    private $companyEmailSender;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var PrepareCompanyData
     */
    private $prepareCompanyData;

    /**
     * @param AccountManagementInterface $accountManagement
     * @param CustomerInterfaceFactory $customerFactory
     * @param ValidateCreateCompanyData $validateCompanyData
     * @param Company $customerCompany
     * @param Sender $companyEmailSender
     * @param UrlInterface $urlBuilder
     * @param PrepareCompanyData $prepareCompanyData
     */
    public function __construct(
        AccountManagementInterface $accountManagement,
        CustomerInterfaceFactory $customerFactory,
        ValidateCreateCompanyData $validateCompanyData,
        Company $customerCompany,
        Sender $companyEmailSender,
        UrlInterface $urlBuilder,
        PrepareCompanyData $prepareCompanyData
    ) {
        $this->accountManagement = $accountManagement;
        $this->customerFactory = $customerFactory;
        $this->validateCompanyData = $validateCompanyData;
        $this->customerCompany = $customerCompany;
        $this->companyEmailSender = $companyEmailSender;
        $this->urlBuilder = $urlBuilder;
        $this->prepareCompanyData = $prepareCompanyData;
    }

    /**
     * Execute of creating company
     *
     * @param array $companyData
     * @param StoreInterface $store
     * @return CompanyInterface
     * @throws GraphQlAlreadyExistsException
     * @throws GraphQlInputException
     */
    public function execute(array $companyData, StoreInterface $store): ?CompanyInterface
    {
        $this->validateCompanyData->execute($companyData);

        $customerDataObject = $this->customerFactory->create([
            'data' => $companyData['company_admin']
        ]);
        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());

        $company = null;
        try {
            $customer = $this->accountManagement->createAccount($customerDataObject);
            $company = $this->createCompany($customer, $companyData);
        } catch (AlreadyExistsException $e) {
            throw new GraphQlAlreadyExistsException(
                __('A customer with the same email address already exists in an associated website.'),
                $e
            );
        } catch (CouldNotSaveException $e) {
            throw new GraphQlInputException(__('The company can not be saved.'), $e);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $company;
    }

    /**
     * Create company with admin user.
     *
     * @param CustomerInterface $customer
     * @param array $companyData
     * @return CompanyInterface|null
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     */
    private function createCompany(CustomerInterface $customer, array $companyData): ?CompanyInterface
    {
        $preparedCompanyData = $this->prepareCompanyData->execute($companyData);
        if (isset($preparedCompanyData['status'])) {
            unset($preparedCompanyData['status']);
        }

        $jobTitle = $preparedCompanyData['company_admin']['job_title'] ?? null;
        $companyDataObject = $this->customerCompany->createCompany($customer, $preparedCompanyData, $jobTitle);
        $companyUrl = $this->urlBuilder->getUrl('company/index/edit', ['id' => $companyDataObject->getId()]);
        $this->companyEmailSender->sendAdminNotificationEmail(
            $customer,
            $companyDataObject->getCompanyName(),
            $companyUrl
        );

        return $companyDataObject;
    }
}
