<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Sales\Api;

use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Company\Api\Data\CompanyOrderInterfaceFactory;
use Magento\Company\Model\ResourceModel\Order;
use Magento\Company\Plugin\Sales\Api\OrderRepositoryInterfacePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OrderRepositoryInterfacePlugin.
 */
class OrderRepositoryInterfacePluginTest extends TestCase
{
    /**
     * @var CompanyOrderInterfaceFactory|MockObject
     */
    private $companyOrderFactory;

    /**
     * @var OrderExtensionFactory|MockObject
     */
    private $orderExtensionAttributesFactory;

    /**
     * @var Order|MockObject
     */
    private $companyOrderResource;

    /**
     * @var OrderRepositoryInterfacePlugin
     */
    private $orderRepositoryInterfacePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyOrderFactory = $this
            ->getMockBuilder(CompanyOrderInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->orderExtensionAttributesFactory = $this
            ->getMockBuilder(OrderExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyOrderResource = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderRepositoryInterfacePlugin = $objectManagerHelper->getObject(
            OrderRepositoryInterfacePlugin::class,
            [
                'companyOrderFactory' => $this->companyOrderFactory,
                'orderExtensionAttributesFactory' => $this->orderExtensionAttributesFactory,
                'companyOrderResource' => $this->companyOrderResource,
            ]
        );
    }

    /**
     * Test method afterGet.
     *
     * @return void
     */
    public function testAfterGet()
    {
        $orderId = 1;
        $companyId = 2;
        $companyName = 'company';
        $companyOrder = $this->getMockBuilder(\Magento\Company\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCompanyId', 'getCompanyName'])
            ->getMockForAbstractClass();
        $companyOrder->expects($this->atLeastOnce())->method('getId')->willReturn($orderId);
        $companyOrder->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $companyOrder->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $companyOrderExtensionAttributes = $this->getMockBuilder(CompanyOrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyOrderExtensionAttributes->expects($this->once())->method('setCompanyId')->with($companyId)
            ->willReturnSelf();
        $companyOrderExtensionAttributes->expects($this->once())->method('setCompanyName')->with($companyName)
            ->willReturnSelf();
        $this->companyOrderFactory->expects($this->atLeastOnce())->method('create')
            ->willReturnOnConsecutiveCalls($companyOrder, $companyOrderExtensionAttributes);
        $this->companyOrderResource->expects($this->once())->method('load')->willReturnSelf();
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $orderExtensionAttributes = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyOrderAttributes'])
            ->getMockForAbstractClass();
        $orderExtensionAttributes->expects($this->once())->method('setCompanyOrderAttributes')->willReturnSelf();
        $order->expects($this->once())->method('getId')->willReturn($orderId);
        $order->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($orderExtensionAttributes);
        $orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertInstanceOf(
            OrderInterface::class,
            $this->orderRepositoryInterfacePlugin->afterGet($orderRepository, $order)
        );
    }
}
