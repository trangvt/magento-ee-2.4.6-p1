<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Adminhtml\Sales\Order\View\Info\Shipment;

use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Company\Block\Adminhtml\Sales\Order\View\Info\Creditmemo\OrderCompanyInfo as OrderCompanyInfoBlock;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrderCompanyInfo object which is responsible for displaying company info on order view page.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderCompanyInfoTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderCompanyInfoBlock
     */
    private $orderCompanyInfo;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var ShipmentRepositoryInterface|MockObject
     */
    private $shipmentRepositoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentRepositoryMock = $this->getMockBuilder(ShipmentRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderCompanyInfo = $this->objectManagerHelper->getObject(
            \Magento\Company\Block\Adminhtml\Sales\Order\View\Info\Shipment\OrderCompanyInfo::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'shipmentRepository' => $this->shipmentRepositoryMock,
                '_request' => $this->requestMock,
                '_urlBuilder' => $this->urlBuilderMock
            ]
        );
    }

    /**
     * Test for getOrder() method.
     *
     * @return void
     */
    public function testGetOrder()
    {
        $companyId = 1;
        $shipmentId = 1;
        $orderId = 1;

        $this->requestMock->expects($this->once())->method('getParam')->with('shipment_id')
            ->willReturn($shipmentId);
        $shipmentMock = $this->getMockBuilder(ShipmentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentRepositoryMock->expects($this->once())->method('get')->with($shipmentId)
            ->willReturn($shipmentMock);
        $shipmentMock->expects($this->once())->method('getOrderId')->willReturn($orderId);
        $this->orderRepositoryMock->expects($this->once())->method('get')->with($orderId)
            ->willReturn($this->orderMock);

        $companyOrderAttributesMock = $this->createCompanyOrderAttributesMock();
        $companyOrderAttributesMock->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);

        $this->assertTrue($this->orderCompanyInfo->canShow());
    }

    /**
     * Create company order attributes mock.
     *
     * @return MockObject
     */
    private function createCompanyOrderAttributesMock()
    {
        $orderExtensionAttributesMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyOrderAttributes'])
            ->getMockForAbstractClass();
        $this->orderMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($orderExtensionAttributesMock);
        $companyOrderAttributesMock = $this->getMockBuilder(CompanyOrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderExtensionAttributesMock->expects($this->atLeastOnce())->method('getCompanyOrderAttributes')
            ->willReturn($companyOrderAttributesMock);

        return $companyOrderAttributesMock;
    }
}
