<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\CompanySuperUserGet;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Company\Model\CustomerRetriever;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Metadata\FormFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CompanySuperUserGet model.
 */
class CompanySuperUserGetTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CompanySuperUserGet
     */
    private $companySuperUserGet;

    /**
     * @var CompanyAttributes|MockObject
     */
    private $companyAttributes;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    private $customerDataFactory;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $dataObjectHelper;

    /**
     * @var AccountManagementInterface|MockObject
     */
    private $accountManagement;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    private $companyCustomer;

    /**
     * @var CustomerRetriever|MockObject
     */
    private $customerRetriever;

    /**
     * @var FormFactory|MockObject
     */
    private $customerFormFactoryMock;

    /**
     * @var Mapper|MockObject
     */
    private $customerMapperMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyAttributes = $this->getMockBuilder(CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerDataFactory = $this->getMockBuilder(
            CustomerInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->accountManagement = $this->getMockBuilder(AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateCustomerStoreIdByWebsiteId'])
            ->getMockForAbstractClass();
        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyCustomer = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRetriever = $this->getMockBuilder(CustomerRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerFormFactoryMock = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerMapperMock = $this->getMockBuilder(Mapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->companySuperUserGet = $this->objectManagerHelper->getObject(
            CompanySuperUserGet::class,
            [
                'companyAttributes' => $this->companyAttributes,
                'customerRepository' => $this->customerRepository,
                'customerDataFactory' => $this->customerDataFactory,
                'dataObjectHelper' => $this->dataObjectHelper,
                'accountManagement' => $this->accountManagement,
                'customerRetriever' => $this->customerRetriever,
                'customerFormFactory' => $this->customerFormFactoryMock,
                'customerMapper' => $this->customerMapperMock,
            ]
        );
    }

    /**
     * Test for getUserForCompanyAdmin method.
     *
     * @return void
     */
    public function testGetUserForCompanyAdmin(): void
    {
        $websiteId = '2';
        $storeId = '2';
        $data = [
            'email' => 'companyadmin@test.com',
            CompanyCustomerInterface::JOB_TITLE => 'Job Title',
            CustomerInterface::WEBSITE_ID => $websiteId,
            'sendemail_store_id' => $storeId
        ];
        $this->customerRetriever
            ->expects($this->once())
            ->method('retrieveForWebsite')
            ->with($data['email'], $websiteId)
            ->willReturn(null);
        $this->prepareMocksForGetUserForCompanyAdmin($data);
        $this->customerDataFactory->method('create')
            ->willReturn($this->customer);
        $this->customer->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(null);
        $this->customer->expects($this->once())
            ->method('setStoreId')
            ->with($storeId);
        $this->companyCustomer->method('getStatus')
            ->willReturn(null);
        $this->companyCustomer->method('setStatus')
            ->with(CompanyCustomerInterface::STATUS_ACTIVE)
            ->willReturnSelf();
        $this->companyCustomer->method('setStatus')
            ->with(CompanyCustomerInterface::STATUS_ACTIVE)
            ->willReturnSelf();
        $this->accountManagement->expects($this->once())
            ->method('validateCustomerStoreIdByWebsiteId')
            ->willReturn(true);
        $this->accountManagement->method('createAccountWithPasswordHash')
            ->with($this->customer, null)
            ->willReturn($this->customer);

        $this->assertEquals($this->customer, $this->companySuperUserGet->getUserForCompanyAdmin($data));
    }

    /**
     * Prepare mocks for testGetUserForCompanyAdmin test.
     *
     * @param array $data
     * @return void
     */
    private function prepareMocksForGetUserForCompanyAdmin($data)
    {
        $customerFormMock = $this->getMockBuilder(\Magento\Customer\Model\Metadata\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerMapperMock->method('toFlatArray')
            ->willReturn([]);
        $customerFormMock->method('extractData')
            ->willReturn($data);
        $customerFormMock->method('compactData')
            ->willReturn($data);
        $requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerFormMock->method('prepareRequest')
            ->willReturn($requestMock);
        $this->customerFormFactoryMock->method('create')
            ->willReturn($customerFormMock);

        $this->dataObjectHelper->method('populateWithArray')
            ->with(
                $this->customer,
                $data,
                CustomerInterface::class
            );
        $this->companyAttributes->method('getCompanyAttributesByCustomer')
            ->with($this->customer)
            ->willReturn($this->companyCustomer);
        $this->companyCustomer->method('setJobTitle')
            ->with($data[CompanyCustomerInterface::JOB_TITLE])
            ->willReturnSelf();
    }

    /**
     * Test for getUserForCompanyAdmin method when customer has ID.
     *
     * @return void
     */
    public function testGetUserForCompanyAdminCustomerHasId(): void
    {
        $websiteId = '2';
        $data = [
            'email' => 'companyadmin@test.com',
            CompanyCustomerInterface::JOB_TITLE => 'Job Title',
            CustomerInterface::WEBSITE_ID => $websiteId
        ];
        $this->customerRetriever->method('retrieveForWebsite')
            ->with($data['email'], $websiteId)
            ->willReturn($this->customer);
        $this->prepareMocksForGetUserForCompanyAdmin($data);
        $this->customer->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $this->companyCustomer->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('dummy status');
        $this->customerRepository->method('save')
            ->with($this->customer)
            ->willReturn($this->customer);

        $this->assertEquals($this->customer, $this->companySuperUserGet->getUserForCompanyAdmin($data));
    }

    /**
     * Test getUserForCompanyAdmin method when LocalizedException is thrown.
     *
     * @return void
     */
    public function testGetUserForCompanyAdminWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('No company admin email is specified in request.');
        $data = [];
        $this->companySuperUserGet->getUserForCompanyAdmin($data);
    }

    /**
     * Test getUserForCompanyAdmin method when LocalizedException is thrown if no website Id is specified.
     *
     * @return void
     */
    public function testGetUserForCompanyAdminWithNoWebsiteIdException(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('No company admin website ID is specified in request.');
        $data = ['email' => 'test@magento.com'];
        $this->companySuperUserGet->getUserForCompanyAdmin($data);
    }
}
