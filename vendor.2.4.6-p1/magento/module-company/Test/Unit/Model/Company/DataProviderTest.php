<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Company;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\Company\DataProvider as SystemUnderTest;
use Magento\Company\Model\ResourceModel\Company\Collection;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\FileUploaderDataResolver;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for testing DataProvider.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProviderTest extends TestCase
{
    public const DATA_PROVIDER_COMPANY_ID = 86;
    public const DATA_PROVIDER_NAME = 'name';
    public const DATA_PROVIDER_PRIMARY_FIELD = 'primary';
    public const DATA_PROVIDER_REQUEST_FIELD = 'request';
    private const SENDEMAIL_STORE_ID = 'sendemail_store_id';

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CollectionFactory|MockObject
     */
    private $companyCollectionFactory;

    /**
     * @var JoinProcessorInterface|MockObject
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var CompanyInterface|MockObject
     * */
    private $company;

    /**
     * @var Collection|MockObject
     * */
    private $collection;

    /**
     * @var CustomerRegistry|MockObject
     */
    private $customerRegistryMock;

    /**
     * @var FileUploaderDataResolver|MockObject
     */
    private $fileUploaderDataResolver;

    /**
     * @var SystemUnderTest
     * */
    private $dataProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $this->companyCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->extensionAttributesJoinProcessor = $this->getMockBuilder(
            JoinProcessorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyCollectionFactory->expects($this->once())->method('create')->willReturn($this->collection);

        $this->customerRegistryMock = $this->getMockBuilder(CustomerRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['retrieve'])
            ->getMock();
        $this->customerRegistryMock->method('retrieve')
            ->willReturnSelf();
        $this->fileUploaderDataResolver = $this->getMockBuilder(FileUploaderDataResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = $objectManager->getObject(
            SystemUnderTest::class,
            [
                'name' => self::DATA_PROVIDER_NAME,
                'primaryFieldName' => self::DATA_PROVIDER_PRIMARY_FIELD,
                'requestFieldName' => self::DATA_PROVIDER_REQUEST_FIELD,
                'companyCollectionFactory' => $this->companyCollectionFactory,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'customerRepository' => $this->customerRepository,
                'customerRegistry' => $this->customerRegistryMock,
                'fileUploaderDataResolver' => $this->fileUploaderDataResolver
            ]
        );
    }

    /**
     * Get general data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    protected function getGeneralData(CompanyInterface $company)
    {
        $name = 'Johnny Mnemonic';
        $status = 'What is love?';
        $companyEmail = 'lorem@ipsum.dolor';
        $rejectReason = 'Any reject reason.';
        $rejectedAt = '2016-07-08 17:03:43';
        $salesRepresentativeId = 373;
        $result = [
            Company::NAME => $name,
            Company::STATUS => $status,
            Company::REJECT_REASON => $rejectReason,
            Company::REJECTED_AT => $rejectedAt,
            Company::COMPANY_EMAIL => $companyEmail,
            Company::SALES_REPRESENTATIVE_ID => $salesRepresentativeId,
        ];
        $company->expects($this->once())->method('getCompanyName')->willReturn($name);
        $company->expects($this->once())->method('getStatus')->willReturn($status);
        $company->expects($this->once())->method('getRejectReason')->willReturn($rejectReason);
        $company->expects($this->once())->method('getRejectedAt')->willReturn($rejectedAt);
        $company->expects($this->once())->method('getCompanyEmail')->willReturn($companyEmail);
        $company->expects($this->once())->method('getSalesRepresentativeId')->willReturn($salesRepresentativeId);
        return $result;
    }

    /**
     * Get company information data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    protected function getInformationData(CompanyInterface $company)
    {
        $legalName = 'John Doe Corp';
        $vatTaxId = 777;
        $resellerId = 555;
        $comment = 'Lorem ipsum dolor';
        $result = [
            Company::LEGAL_NAME => $legalName,
            Company::VAT_TAX_ID => $vatTaxId,
            Company::RESELLER_ID => $resellerId,
            Company::COMMENT => $comment,
        ];
        $company->expects($this->once())->method('getLegalName')->willReturn($legalName);
        $company->expects($this->once())->method('getVatTaxId')->willReturn($vatTaxId);
        $company->expects($this->once())->method('getResellerId')->willReturn($resellerId);
        $company->expects($this->once())->method('getComment')->willReturn($comment);
        return $result;
    }

    /**
     * Get legal address data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    protected function getAddressData(CompanyInterface $company)
    {
        $street = 'Tank st.111';
        $city = 'Down Uryupinsk';
        $countryId = 42;
        $region = 'Uryupinsk';
        $regionId = 13;
        $postCode = '1234567/';
        $telephone = '555-1234';
        $result = [
            Company::STREET => $street,
            Company::CITY => $city,
            Company::COUNTRY_ID => $countryId,
            Company::REGION => $region,
            Company::REGION_ID => $regionId,
            Company::POSTCODE => $postCode,
            Company::TELEPHONE => $telephone,
        ];
        $company->expects($this->once())->method('getStreet')->willReturn($street);
        $company->expects($this->once())->method('getCity')->willReturn($city);
        $company->expects($this->once())->method('getCountryId')->willReturn($countryId);
        $company->expects($this->once())->method('getRegion')->willReturn($region);
        $company->expects($this->once())->method('getRegionId')->willReturn($regionId);
        $company->expects($this->once())->method('getPostcode')->willReturn($postCode);
        $company->expects($this->once())->method('getTelephone')->willReturn($telephone);
        return $result;
    }

    /**
     * Get company admin data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    protected function getCompanyAdminData(CompanyInterface $company): array
    {
        $userId = 4;
        $jobTitle = 'CTO';
        $prefix = 'Mr';
        $firstName = 'John';
        $middleName = 'Lost';
        $lastName = 'Doe';
        $suffix = 'Endangerous';
        $email = 'john@lost.doe';
        $storeId = 1;
        $gender = 'Male';
        $websiteId = '2';
        $createdIn = 'Default Store View';
        $result = [
            Company::JOB_TITLE => $jobTitle,
            Company::PREFIX => $prefix,
            Company::FIRSTNAME => $firstName,
            Company::MIDDLENAME => $middleName,
            Company::LASTNAME => $lastName,
            Company::SUFFIX => $suffix,
            Company::EMAIL => $email,
            self::SENDEMAIL_STORE_ID => $storeId,
            Company::GENDER => $gender,
            CustomerInterface::WEBSITE_ID => $websiteId,
            CustomerInterface::CREATED_IN => $createdIn,
        ];
        $company->expects($this->exactly(2))->method('getSuperUserId')->willReturn($userId);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getCustomAttributes'])
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $customer->expects($this->once())->method('getCustomAttributes')->willReturn([]);
        $extensionAttributes->expects($this->once())->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getJobTitle')->willReturn($jobTitle);
        $customer->expects($this->once())->method('getPrefix')->willReturn($prefix);
        $customer->expects($this->once())->method('getFirstname')->willReturn($firstName);
        $customer->expects($this->once())->method('getMiddlename')->willReturn($middleName);
        $customer->expects($this->once())->method('getLastname')->willReturn($lastName);
        $customer->expects($this->once())->method('getSuffix')->willReturn($suffix);
        $customer->expects($this->once())->method('getEmail')->willReturn($email);
        $customer->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $customer->expects($this->once())->method('getGender')->willReturn($gender);
        $customer->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $customer->expects($this->once())->method('getCreatedIn')->willReturn($createdIn);
        $this->customerRepository->expects($this->once())
            ->method('getById')->with($userId)->willReturn($customer);
        $this->fileUploaderDataResolver->expects($this->once())
            ->method('overrideFileUploaderData');

        return $result;
    }

    /**
     * Get advanced settings data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    protected function getSettingsData(CompanyInterface $company)
    {
        $customerGroupId = 2;
        $result = [
            Company::CUSTOMER_GROUP_ID => $customerGroupId,
        ];
        $company->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($customerGroupId);
        return $result;
    }

    /**
     * Test for getCompanyResultData method.
     *
     * @return void
     */
    public function testGetCompanyResultData()
    {
        $expected = [
            SystemUnderTest::DATA_SCOPE_GENERAL => $this->getGeneralData($this->company),
            SystemUnderTest::DATA_SCOPE_INFORMATION => $this->getInformationData($this->company),
            SystemUnderTest::DATA_SCOPE_ADDRESS => $this->getAddressData($this->company),
            SystemUnderTest::DATA_SCOPE_COMPANY_ADMIN => $this->getCompanyAdminData($this->company),
            SystemUnderTest::DATA_SCOPE_SETTINGS => $this->getSettingsData($this->company),
            'id' => self::DATA_PROVIDER_COMPANY_ID,
        ];

        $this->company->expects($this->once())->method('getId')->willReturn(self::DATA_PROVIDER_COMPANY_ID);
        $result = $this->dataProvider->getCompanyResultData($this->company);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getData method.
     *
     * @return void
     */
    public function testGetData()
    {
        $expected = [
            self::DATA_PROVIDER_COMPANY_ID => [
                SystemUnderTest::DATA_SCOPE_GENERAL => $this->getGeneralData($this->company),
                SystemUnderTest::DATA_SCOPE_INFORMATION => $this->getInformationData($this->company),
                SystemUnderTest::DATA_SCOPE_ADDRESS => $this->getAddressData($this->company),
                SystemUnderTest::DATA_SCOPE_COMPANY_ADMIN => $this->getCompanyAdminData($this->company),
                SystemUnderTest::DATA_SCOPE_SETTINGS => $this->getSettingsData($this->company),
                'id' => self::DATA_PROVIDER_COMPANY_ID,
            ]
        ];
        $this->extensionAttributesJoinProcessor->expects($this->once())->method('process')->with($this->collection);
        $this->collection->expects($this->once())->method('getItems')->willReturn([$this->company]);
        $this->company->expects($this->atLeastOnce())->method('getId')->willReturn(self::DATA_PROVIDER_COMPANY_ID);

        $this->assertEquals($expected, $this->dataProvider->getData());
    }
}
