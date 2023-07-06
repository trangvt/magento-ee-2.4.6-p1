<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Order;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyOrderInterfaceFactory;
use Magento\Company\Controller\Order\OrderViewAuthorization;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\ResourceModel\Order as CompanyOrderResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Company\Model\Order as CompanyOrder;
use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for OrderViewAuthorization
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderViewAuthorizationTest extends TestCase
{
    /**
     * @var OrderViewAuthorization
     */
    private $orderViewAuthorization;

    /**
     * @var Structure|MockObject
     */
    private $structureMock;

    /**
     * @var Config|MockObject
     */
    private $orderConfigMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CompanyOrderInterfaceFactory|MockObject
     */
    private $companyOrderFactoryMock;

    /**
     * @var CompanyOrderInterface|MockObject
     */
    private $companyOrderMock;

    /**
     * @var CompanyOrderResource|MockObject
     */
    private $companyOrderResourceMock;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->structureMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllowedChildrenIds'])
            ->getMock();
        $this->orderConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVisibleOnFrontStatuses'])
            ->getMock();
        $this->userContextMock = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId'])
            ->getMockForAbstractClass();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->companyOrderFactoryMock = $this->getMockBuilder(CompanyOrderInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->companyOrderMock = $this->getMockBuilder(CompanyOrder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId', 'getOrderId'])
            ->getMock();
        $this->companyOrderResourceMock = $this->getMockBuilder(CompanyOrderResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();

        $this->orderMock =$this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCustomerId', 'getStatus', 'getExtensionAttributes'])
            ->getMock();

        $this->orderViewAuthorization = new OrderViewAuthorization(
            $this->structureMock,
            $this->orderConfigMock,
            $this->userContextMock,
            $this->customerRepositoryMock,
            $this->companyOrderFactoryMock,
            $this->companyOrderResourceMock
        );
    }

    /**
     * When order status is not in list of visible on frontend statuses, canView returns false
     */
    public function testCanViewWhenOrderStatusIsNotViewableOnFrontend()
    {
        $this->orderConfigMock->expects($this->any())
            ->method('getVisibleOnFrontStatuses')
            ->willReturn([
                'completed',
                'viewed'
            ]);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('incomplete');

        $this->assertFalse($this->orderViewAuthorization->canView($this->orderMock));
    }

    /**
     * When order status is not a company order and order customer id is != customerId, canView returns false
     */
    public function testCanViewWhenNonCompanyOrderDoesNotBelongToCustomer()
    {
        $this->orderConfigMock->expects($this->any())
            ->method('getVisibleOnFrontStatuses')
            ->willReturn([
                'completed',
                'viewed'
            ]);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('completed');

        $customerId = 1;
        $orderId = 2;
        $orderCustomerId = 2;

        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->orderMock->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $this->orderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($orderCustomerId);

        $this->companyOrderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->companyOrderMock);

        $this->companyOrderResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->companyOrderMock, $orderId, CompanyOrderInterface::ORDER_ID)
            ->willReturn($this->companyOrderMock);

        $this->assertFalse($this->orderViewAuthorization->canView($this->orderMock));
    }

    /**
     * When order status is not a company order and order customer id is == customerId, canView returns true
     */
    public function testCanViewWhenNonCompanyOrderDoesBelongToCustomer()
    {
        $this->orderConfigMock->expects($this->any())
            ->method('getVisibleOnFrontStatuses')
            ->willReturn([
                'completed',
                'viewed'
            ]);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('completed');

        $customerId = 1;
        $orderId = 2;
        $orderCustomerId = 1;

        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->orderMock->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $this->orderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($orderCustomerId);

        $this->companyOrderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->companyOrderMock);

        $this->companyOrderResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->companyOrderMock, $orderId, CompanyOrderInterface::ORDER_ID)
            ->willReturn($this->companyOrderMock);

        $this->assertTrue($this->orderViewAuthorization->canView($this->orderMock));
    }

    /**
     * When order is a company order and customer company id is != order company id, canView returns false
     */
    public function testCanViewWhenCompanyIdOfOrderIsNotEqualToCustomerCompanyId()
    {
        $this->orderConfigMock->expects($this->any())
            ->method('getVisibleOnFrontStatuses')
            ->willReturn([
                'completed',
                'viewed'
            ]);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('completed');

        $customerId = 1;
        $orderOrderId = 2;
        $orderCustomerId = 1;
        $customerCompanyId = 20;
        $orderCompanyId = 30;

        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->orderMock->expects($this->any())
            ->method('getId')
            ->willReturn($orderOrderId);

        $this->orderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($orderCustomerId);

        $this->companyOrderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->companyOrderMock);

        $this->companyOrderResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->companyOrderMock, $orderOrderId, CompanyOrderInterface::ORDER_ID)
            ->willReturn($this->companyOrderMock);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customer);

        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();

        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);

        $customerCompanyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customerExtensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturn($customerCompanyAttributes);

        $customerCompanyAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($customerCompanyId);

        $this->companyOrderMock
            ->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderOrderId);

        $this->companyOrderMock
            ->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($orderCompanyId);

        $this->assertFalse($this->orderViewAuthorization->canView($this->orderMock));
    }

    /**
     * When order is a company order, customer company id is == order company id,
     * but customer order id != customer id, canView returns false
     */
    public function testCanViewWhenOrderCustomerIdIsNotEqualToCompanyCustomerId()
    {
        $this->orderConfigMock->expects($this->any())
            ->method('getVisibleOnFrontStatuses')
            ->willReturn([
                'completed',
                'viewed'
            ]);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('completed');

        $customerId = 1;
        $orderOrderId = 2;
        $orderCustomerId = 300;
        $customerCompanyId = 20;
        $orderCompanyId = 20;

        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->orderMock->expects($this->any())
            ->method('getId')
            ->willReturn($orderOrderId);

        $this->orderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($orderCustomerId);

        $this->companyOrderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->companyOrderMock);

        $this->companyOrderResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->companyOrderMock, $orderOrderId, CompanyOrderInterface::ORDER_ID)
            ->willReturn($this->companyOrderMock);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customer);

        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();

        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);

        $customerCompanyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customerExtensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturn($customerCompanyAttributes);

        $customerCompanyAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($customerCompanyId);

        $this->companyOrderMock
            ->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderOrderId);

        $this->companyOrderMock
            ->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($orderCompanyId);

        $this->structureMock->expects($this->any())
            ->method('getAllowedChildrenIds')
            ->with($customerId)
            ->willReturn([]);

        $this->assertFalse($this->orderViewAuthorization->canView($this->orderMock));
    }

    /**
     * When order is a company order, customer company id is == order company id,
     * and customer order id == customer id, canView returns true
     */
    public function testCanViewWhenOrderCustomerIdIsEqualToCompanyCustomerId()
    {
        $this->orderConfigMock->expects($this->any())
            ->method('getVisibleOnFrontStatuses')
            ->willReturn([
                'completed',
                'viewed'
            ]);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('completed');

        $customerId = 1;
        $orderOrderId = 2;
        $orderCustomerId = 1;
        $customerCompanyId = 20;
        $orderCompanyId = 20;

        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->orderMock->expects($this->any())
            ->method('getId')
            ->willReturn($orderOrderId);

        $this->orderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($orderCustomerId);

        $this->companyOrderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->companyOrderMock);

        $this->companyOrderResourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->companyOrderMock, $orderOrderId, CompanyOrderInterface::ORDER_ID)
            ->willReturn($this->companyOrderMock);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customer);

        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();

        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);

        $customerCompanyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customerExtensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturn($customerCompanyAttributes);

        $customerCompanyAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($customerCompanyId);

        $this->companyOrderMock
            ->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderOrderId);

        $this->companyOrderMock
            ->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($orderCompanyId);

        $this->structureMock->expects($this->any())
            ->method('getAllowedChildrenIds')
            ->with($customerId)
            ->willReturn([]);

        $this->assertTrue($this->orderViewAuthorization->canView($this->orderMock));
    }
}
