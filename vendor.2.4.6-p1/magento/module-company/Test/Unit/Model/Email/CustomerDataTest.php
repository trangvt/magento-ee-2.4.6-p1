<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Email;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Email\CustomerData;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\User\Api\Data\UserInterfaceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerDataTest extends TestCase
{
    /**
     * @var CustomerData
     */
    private $customerData;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder|MockObject
     */
    private $transportBuilder;

    /**
     * @var DataObjectProcessor|MockObject
     */
    private $dataProcessor;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelper;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var UserInterfaceFactory|MockObject
     */
    private $userFactory;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * setUp
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $website = $this->createPartialMock(Website::class, ['getStoreIds']);
        $this->storeManager->expects($this->any())->method('getWebsite')->willReturn($website);
        $website->expects($this->any())->method('getStoreIds')->willReturn([1, 2, 3]);

        $this->scopeConfig = $this->createMock(
            ScopeConfigInterface::class
        );
        $this->transportBuilder = $this->createPartialMock(
            TransportBuilder::class,
            ['setFrom', 'addTo', 'getTransport']
        );
        $this->transportBuilder->expects($this->any())
            ->method('setFrom')->willReturnSelf();
        $transport = $this->getMockForAbstractClass(TransportInterface::class);
        $this->transportBuilder->expects($this->any())
            ->method('getTransport')->willReturn($transport);

        $this->dataProcessor = $this->createMock(
            DataObjectProcessor::class
        );
        $this->dataProcessor->expects($this->any())
            ->method('buildOutputDataArray')->willReturn([]);
        $this->customerViewHelper = $this->createMock(
            CustomerNameGenerationInterface::class
        );

        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );

        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $companyModel = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getName', 'getSalesRepresentativeId'])
            ->getMockForAbstractClass();

        $companyModel->expects($this->any())->method('getName')->willReturn('Company Name');
        $companyModel->expects($this->any())->method('getSalesRepresentativeId')->willReturn(1);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($companyModel);
        $this->userFactory = $this->createPartialMock(
            UserInterfaceFactory::class,
            ['create']
        );

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getStoreId', 'getWebsiteId', 'getEmail', 'getName', 'load'])
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->customerData = $objectManagerHelper->getObject(
            CustomerData::class,
            [
                'dataProcessor' => $this->dataProcessor,
                'customerViewHelper' => $this->customerViewHelper,
                'companyRepository' => $this->companyRepository,
                'userFactory' => $this->userFactory,
                'customerRepository' => $this->customerRepository,
            ]
        );
    }

    /**
     * test getDataObjectByCustomer
     * @return void
     */
    public function testGetDataObjectByCustomer()
    {
        $this->customer->expects($this->any())->method('getStoreId')->willReturn(0);
        $this->customer->expects($this->any())->method('getWebsiteId')->willReturn(2);
        $this->customer->expects($this->any())->method('getEmail')->willReturn('example@text.com');
        $this->customerViewHelper->expects($this->any())->method('getCustomerName')->willReturn('test');

        $this->assertInstanceOf(
            DataObject::class,
            $this->customerData->getDataObjectByCustomer($this->customer, 1)
        );
    }

    /**
     * test getDataObjectByCustomer
     * @return void
     */
    public function testGetDataObjectByCustomerEmpty()
    {
        $this->customer->expects($this->any())->method('getStoreId')->willReturn(0);
        $this->customer->expects($this->any())->method('getWebsiteId')->willReturn(2);
        $this->customer->expects($this->any())->method('getEmail')->willReturn('example@text.com');
        $this->customerViewHelper->expects($this->any())->method('getCustomerName')->willReturn('test');

        $this->assertInstanceOf(
            DataObject::class,
            $this->customerData->getDataObjectByCustomer($this->customer, null)
        );
    }

    /**
     * test getDataObjectSuperUser
     * @return void
     */
    public function testGetDataObjectSuperUser()
    {
        $this->customer->expects($this->any())->method('getStoreId')->willReturn(0);
        $this->customer->expects($this->any())->method('getWebsiteId')->willReturn(2);
        $this->customer->expects($this->any())->method('getEmail')->willReturn('example@text.com');
        $this->customerViewHelper->expects($this->any())->method('getCustomerName')->willReturn('test');
        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customer);

        $this->assertInstanceOf(DataObject::class, $this->customerData->getDataObjectSuperUser(1));
    }

    /**
     * test getDataObjectSalesRepresentative
     * @return void
     */
    public function testGetDataObjectSalesRepresentative()
    {
        $this->customer->expects($this->any())->method('getStoreId')->willReturn(0);
        $this->customer->expects($this->any())->method('getWebsiteId')->willReturn(2);
        $this->customer->expects($this->any())->method('getName')->willReturn('test');
        $this->customer->expects($this->any())->method('getEmail')->willReturn('example@text.com');
        $this->customer->expects($this->any())->method('load')->willReturnSelf();

        $this->userFactory->expects($this->any())->method('create')->willReturn($this->customer);

        $this->assertInstanceOf(
            DataObject::class,
            $this->customerData->getDataObjectSalesRepresentative(1, 2)
        );
    }
}
