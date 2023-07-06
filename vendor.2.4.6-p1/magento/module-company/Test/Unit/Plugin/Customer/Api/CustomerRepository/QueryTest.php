<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Customer\Api\CustomerRepository;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Company\Plugin\Customer\Api\CustomerRepository\Query;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Magento\Company\Plugin\Customer\Api\CustomerRepository\Query class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QueryTest extends TestCase
{
    /**
     * @var CompanyCustomerInterface|MockObject
     */
    private $customerAttributes;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Query|MockObject
     */
    private $customerSave;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var ExtensionAttributesFactory|MockObject
     */
    private $extensionFactory;

    /**
     * @var CustomerExtensionInterface|MockObject
     */
    private $customerExtension;

    /**
     * @var CompanyCustomerInterfaceFactory|MockObject
     */
    private $companyCustomerAttributes;

    /**
     * @var CompanyAttributes|MockObject
     */
    private $customerSaveAttributes;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $dataObjectHelper;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->extensionFactory = $this->getMockBuilder(ExtensionAttributesFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyCustomerAttributes = $this->getMockBuilder(CompanyCustomerInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSaveAttributes = $this->getMockBuilder(CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->customerSave = $objectManagerHelper->getObject(
            Query::class,
            [
                'extensionFactory' => $this->extensionFactory,
                'companyCustomerAttributes' => $this->companyCustomerAttributes,
                'customerSaveAttributes' => $this->customerSaveAttributes,
                'dataObjectHelper' => $this->dataObjectHelper
            ]
        );
    }

    /**
     * Test afterGet with company attributes.
     *
     * @return void
     */
    public function testAfterGetWithCompanyAttributes()
    {
        $this->extensionFactory->expects($this->once())
            ->method('create')
            ->with(CustomerInterface::class)
            ->willReturn($this->customerExtension);

        $this->assertEquals(
            $this->customer,
            $this->customerSave->afterGet($this->customerRepository, $this->customer)
        );
    }

    /**
     * Test afterGet.
     *
     * @return void
     */
    public function testAfterGet()
    {
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->setMethods(['getCompanyAttributes', 'setCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtension->expects($this->atLeastOnce())->method('getCompanyAttributes')->willReturn(false);
        $this->customer->expects($this->any())->method('getExtensionAttributes')->willReturn($customerExtension);

        $companyAttributes = ['customer_id' => 1];
        $this->customerSaveAttributes->expects($this->once())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $this->companyCustomerAttributes->expects($this->once())
            ->method('create')->willReturn($this->customerAttributes);
        $this->dataObjectHelper->expects($this->once())->method('populateWithArray')
            ->with(
                $this->customerAttributes,
                $companyAttributes,
                CompanyCustomerInterface::class
            )->willReturnSelf();

        $this->assertEquals(
            $this->customer,
            $this->customerSave->afterGet($this->customerRepository, $this->customer)
        );
    }

    /**
     * Test afterGet with Exception.
     *
     * @return void
     */
    public function testAfterGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Something went wrong');
        $this->extensionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->customerExtension);
        $this->customerSaveAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willThrowException(new \Exception());
        $this->customerSave->afterGet($this->customerRepository, $this->customer);
    }

    /**
     * Test 'getCustomer' method.
     *
     * @return void
     */
    public function testGetCustomer()
    {
        $dataObject = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject->expects($this->atLeastOnce())->method('getCompanyAttributes')->willReturn(true);
        $this->customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($dataObject);
        $this->customerSave->afterGet($this->customerRepository, $this->customer);
    }

    /**
     * Test for method afterGetById.
     *
     * @return void
     */
    public function testAfterGetById()
    {
        $dataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($dataObject);
        $companyAttributes = ['customer_id' => 1];
        $this->customerSaveAttributes->expects($this->once())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $this->companyCustomerAttributes->expects($this->once())
            ->method('create')->willReturn($this->customerAttributes);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->with($this->customerAttributes, $companyAttributes, CompanyCustomerInterface::class)
            ->willReturnSelf();

        $this->assertEquals(
            $this->customer,
            $this->customerSave->afterGetById($this->customerRepository, $this->customer)
        );
    }
}
