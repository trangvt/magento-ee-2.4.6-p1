<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Gateway\Config;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\CompanyContext;
use Magento\CompanyCredit\Gateway\Config\ActiveHandler;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CompanyCredit\Gateway\Config\ActiveHandler.
 */
class ActiveHandlerTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ActiveHandler
     */
    private $activeHandler;

    /**
     * @var ConfigInterface|MockObject
     */
    private $configInterfaceMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContextMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->userContextMock = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyContextMock = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->subjectReader = $this->objectManagerHelper->getObject(
            SubjectReader::class
        );
        $this->activeHandler = $this->objectManagerHelper->getObject(
            ActiveHandler::class,
            [
                'configInterface'    => $this->configInterfaceMock,
                'customerRepository' => $this->customerRepositoryMock,
                'companyContext'     => $this->companyContextMock,
                'subjectReader'      => $this->subjectReader,
                'userContext' => $this->userContextMock
            ]
        );
    }

    /**
     * Test for handle() method if current user is Admin.
     *
     * @return void
     */
    public function testHandleIfUserAdmin()
    {
        $subject = ['field' => 'active'];
        $field = $subject['field'];
        $storeId = 1;

        $this->configInterfaceMock->expects($this->once())->method('getValue')->with(
            $field,
            $storeId
        )->willReturn("1");
        $this->userContextMock->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);

        $this->assertTrue($this->activeHandler->handle($subject, $storeId));
    }

    /**
     * Test for handle() method of current user is Customer.
     *
     * @return void
     */
    public function testHandleIfUserCustomer()
    {
        $subject = ['field' => 'active'];
        $field = $subject['field'];
        $storeId = 1;
        $customerId = 1;

        $this->configInterfaceMock->expects($this->once())->method('getValue')->with(
            $field,
            $storeId
        )->willReturn("1");
        $this->userContextMock->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);

        $this->companyContextMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtensionAttributes = $this->getMockBuilder(
            CustomerExtensionInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $customerAttributesMock = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtensionAttributes->expects($this->exactly(2))->method('getCompanyAttributes')
            ->willReturn($customerAttributesMock);
        $customerAttributesMock->expects($this->once())->method('getStatus')->willReturn(1);
        $customerMock->expects($this->exactly(3))->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);
        $this->customerRepositoryMock->expects($this->once())->method('getById')->with($customerId)
            ->willReturn($customerMock);

        $this->assertTrue($this->activeHandler->handle($subject, $storeId));
    }

    /**
     * Test for handle() method if there is no configured value for it.
     *
     * @return void
     */
    public function testHandleNoConfigValue()
    {
        $subject = ['field' => 'active'];
        $field = $subject['field'];
        $storeId = 1;

        $this->configInterfaceMock->expects($this->once())->method('getValue')->with(
            $field,
            $storeId
        )->willReturn(null);
        $this->userContextMock->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);

        $this->assertFalse($this->activeHandler->handle($subject, $storeId));
    }

    /**
     * Test for handle() method with NoSuchEntityException.
     *
     * @return void
     */
    public function testHandleWithNoSuchEntityException()
    {
        $subject = ['field' => 'active'];
        $field = $subject['field'];
        $storeId = 1;
        $customerId = 1;

        $this->configInterfaceMock->expects($this->once())->method('getValue')->with(
            $field,
            $storeId
        )->willReturn("1");
        $this->userContextMock->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);

        $this->companyContextMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $exception = new NoSuchEntityException();
        $this->customerRepositoryMock->expects($this->once())->method('getById')->with($customerId)
            ->willThrowException($exception);

        $this->assertFalse($this->activeHandler->handle($subject, $storeId));
    }
}
