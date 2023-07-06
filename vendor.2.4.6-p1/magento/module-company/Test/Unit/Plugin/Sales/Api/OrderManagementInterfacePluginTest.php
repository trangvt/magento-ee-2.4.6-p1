<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Sales\Api;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Company\Api\Data\CompanyOrderInterfaceFactory;
use Magento\Company\Plugin\Sales\Api\OrderManagementInterfacePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OrderManagementInterfacePlugin.
 */
class OrderManagementInterfacePluginTest extends TestCase
{
    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var CompanyOrderInterfaceFactory|MockObject
     */
    private $companyOrderFactory;

    /**
     * @var OrderExtensionFactory|MockObject
     */
    private $orderExtensionAttributesFactory;

    /**
     * @var OrderManagementInterfacePlugin
     */
    private $orderManagementInterfacePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
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

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderManagementInterfacePlugin = $objectManagerHelper->getObject(
            OrderManagementInterfacePlugin::class,
            [
                'companyManagement' => $this->companyManagement,
                'companyOrderFactory' => $this->companyOrderFactory,
                'orderExtensionAttributesFactory' => $this->orderExtensionAttributesFactory,
            ]
        );
    }

    /**
     * Test method beforePlace().
     *
     * @return void
     */
    public function testBeforePlace()
    {
        $customerId = 1;
        $companyId = 2;
        $companyName = 'company';
        $orderExtensionAttributes = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyOrderAttributes'])
            ->getMockForAbstractClass();
        $orderExtensionAttributes->expects($this->once())->method('setCompanyOrderAttributes')->willReturnSelf();
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $order->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $order->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($orderExtensionAttributes);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->with($customerId)
            ->willReturn($company);
        $companyOrderExtensionAttributes = $this->getMockBuilder(CompanyOrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyOrderExtensionAttributes->expects($this->once())->method('setCompanyId')->with($companyId)
            ->willReturnSelf();
        $companyOrderExtensionAttributes->expects($this->once())->method('setCompanyName')->with($companyName)
            ->willReturnSelf();
        $this->companyOrderFactory->expects($this->once())->method('create')
            ->willReturn($companyOrderExtensionAttributes);
        $orderManagement = $this->getMockBuilder(OrderManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result = $this->orderManagementInterfacePlugin->beforePlace($orderManagement, $order);

        $this->assertInstanceOf(
            OrderInterface::class,
            $result[0]
        );
    }

    /**
     * Test for afterPlace method.
     *
     * @return void
     */
    public function testAfterPlace()
    {
        $orderId = 1;
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderExtensionAttributes = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyOrderAttributes'])
            ->getMockForAbstractClass();
        $order->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($orderExtensionAttributes);
        $companyAttributes = $this->getMockBuilder(CompanyOrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'setOrderId'])
            ->getMockForAbstractClass();
        $orderExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyOrderAttributes')->willReturn($companyAttributes);
        $order->expects($this->once())->method('getEntityId')->willReturn($orderId);
        $companyAttributes->expects($this->once())->method('setOrderId')->with($orderId)->willReturnSelf();
        $companyAttributes->expects($this->once())->method('save')->willReturnSelf();
        $orderManagement = $this->getMockBuilder(OrderManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->assertEquals($order, $this->orderManagementInterfacePlugin->afterPlace($orderManagement, $order));
    }
}
