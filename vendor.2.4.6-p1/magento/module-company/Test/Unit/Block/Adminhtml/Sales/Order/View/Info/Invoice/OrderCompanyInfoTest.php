<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Adminhtml\Sales\Order\View\Info\Invoice;

use Magento\Company\Api\Data\CompanyOrderInterface;
use Magento\Company\Block\Adminhtml\Sales\Order\View\Info\Invoice\OrderCompanyInfo as OrderCompanyInfoBlock;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
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
     * @var \Magento\Company\Block\Adminhtml\Sales\Order\View\Info\Creditmemo\OrderCompanyInfo
     */
    private $orderCompanyInfo;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var InvoiceRepositoryInterface|MockObject
     */
    private $invoiceRepositoryMock;

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
        $this->invoiceRepositoryMock = $this->getMockBuilder(InvoiceRepositoryInterface::class)
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
            OrderCompanyInfoBlock::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'invoiceRepository' => $this->invoiceRepositoryMock,
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
        $invoiceId = 1;
        $orderId = 1;

        $this->requestMock->expects($this->once())->method('getParam')->with('invoice_id')
            ->willReturn($invoiceId);
        $invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->invoiceRepositoryMock->expects($this->once())->method('get')->with($invoiceId)
            ->willReturn($invoiceMock);
        $invoiceMock->expects($this->once())->method('getOrderId')->willReturn($orderId);
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
