<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Creates or updates a company admin customer entity with given data during company save process in admin panel.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanySuperUserGet
{
    /**
     * @var CompanyAttributes
     */
    private $companyAttributes;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerDataFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var CustomerRetriever
     */
    private $customerRetriever;

    /**
     * @var FormFactory
     */
    private $customerFormFactory;

    /**
     * @var Mapper
     */
    private $customerMapper;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param CompanyAttributes $companyAttributes
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRetriever $customerRetriever
     * @param FormFactory $customerFormFactory
     * @param Mapper $customerMapper
     * @param DateTime $dateTime
     */
    public function __construct(
        CompanyAttributes $companyAttributes,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerDataFactory,
        DataObjectHelper $dataObjectHelper,
        AccountManagementInterface $accountManagement,
        CustomerRetriever $customerRetriever,
        FormFactory $customerFormFactory,
        Mapper $customerMapper,
        DateTime $dateTime
    ) {
        $this->companyAttributes = $companyAttributes;
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->accountManagement = $accountManagement;
        $this->customerRetriever = $customerRetriever;
        $this->customerFormFactory = $customerFormFactory;
        $this->customerMapper = $customerMapper;
        $this->dateTime = $dateTime;
    }

    /**
     * Get company admin user or create one if it does not exist.
     *
     * @param array $data
     * @return CustomerInterface
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getUserForCompanyAdmin(array $data): CustomerInterface
    {
        unset($data['extension_attributes']);

        if (!isset($data['email'])) {
            throw new LocalizedException(
                __('No company admin email is specified in request.')
            );
        }
        if (!isset($data['website_id'])) {
            throw new LocalizedException(
                __('No company admin website ID is specified in request.')
            );
        }
        $companyAdminEmail = $data['email'];
        $websiteId = $data['website_id'];
        $customer = $this->customerRetriever->retrieveForWebsite(
            $companyAdminEmail,
            $websiteId
        );
        if (!$customer) {
            $customer = $this->customerDataFactory->create();
        }
        $data = $this->extractCustomerData($data, $customer);
        $this->dataObjectHelper->populateWithArray(
            $customer,
            $data,
            CustomerInterface::class
        );

        if (isset($data['sendemail_store_id']) && $data['sendemail_store_id'] !== false) {
            $customer->setStoreId($data['sendemail_store_id']);
            try {
                $this->accountManagement->validateCustomerStoreIdByWebsiteId($customer);
            } catch (LocalizedException $exception) {
                throw new LocalizedException(__("The Store View selected for sending Welcome email from".
                    " is not related to the customer's associated website."));
            }
        }

        $companyAttributes = $this->companyAttributes->getCompanyAttributesByCustomer($customer);
        $customerStatus = $customer->getId() ?
            $companyAttributes->getStatus() : CompanyCustomerInterface::STATUS_ACTIVE;
        if (isset($data[CompanyCustomerInterface::JOB_TITLE])) {
            $companyAttributes->setJobTitle($data[CompanyCustomerInterface::JOB_TITLE]);
        }
        if (!$companyAttributes->getStatus()) {
            $companyAttributes->setStatus($customerStatus);
        }
        if ($customer->getId()) {
            $customer = $this->customerRepository->save($customer);
        } else {
            if (!$customer->getCreatedIn()) {
                $createdAt = $this->dateTime->gmtDate('Y-m-d H:i:s');
                $customer->setCreatedAt($createdAt);
            }
            $customer = $this->accountManagement->createAccountWithPasswordHash($customer, null);
        }

        return $customer;
    }

    /**
     * Extract customer data from request
     *
     * @param array $data
     * @param CustomerInterface $customer
     *
     * @return array
     */
    private function extractCustomerData(array $data, CustomerInterface $customer): array
    {
        $metadataForm = $this->getMetadataForm($customer);
        $customerRequest = $metadataForm->prepareRequest($data);
        $formData = $metadataForm->extractData($customerRequest);

        return $metadataForm->compactData($formData);
    }

    /**
     * Get metadata form for company admin
     *
     * @param CustomerInterface $customer
     *
     * @return Form
     */
    private function getMetadataForm(CustomerInterface $customer)
    {
        $attributeValues = [];
        if ($customer->getId()) {
            $attributeValues = $this->customerMapper->toFlatArray($customer);
        }

        return $this->customerFormFactory->create(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'adminhtml_customer',
            $attributeValues,
            false,
            Form::DONT_IGNORE_INVISIBLE
        );
    }
}
