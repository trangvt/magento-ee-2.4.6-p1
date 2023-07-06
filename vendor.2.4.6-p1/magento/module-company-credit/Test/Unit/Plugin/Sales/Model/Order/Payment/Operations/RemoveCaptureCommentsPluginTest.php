<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Sales\Model\Order\Payment\Operations;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Plugin\Sales\Model\Order\Payment\Operations\RemoveCaptureCommentsPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Operations\CaptureOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RemoveCaptureCommentsPlugin.
 */
class RemoveCaptureCommentsPluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CaptureOperation|MockObject
     */
    private $subjectMock;

    /**
     * @var OrderPaymentInterface|MockObject
     */
    private $paymentMock;

    /**
     * @var RemoveCaptureCommentsPlugin
     */
    private $removeCaptureCommentsPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(CaptureOperation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->removeCaptureCommentsPlugin = $this->objectManagerHelper->getObject(
            RemoveCaptureCommentsPlugin::class,
            []
        );
    }

    /**
     * Test for aroundCapture() method.
     *
     * @return void
     */
    public function testAroundCapture()
    {
        $invoice = null;
        $statusHistories = null;

        $this->paymentMock->expects($this->atLeastOnce())->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock->expects($this->atLeastOnce())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->atLeastOnce())->method('getStatusHistories')->willReturn($statusHistories);
        $orderMock->expects($this->atLeastOnce())->method('setStatusHistories')->with($statusHistories)
            ->willReturnSelf();

        $closure = function ($paymentMock) {
            return $paymentMock;
        };

        $this->assertEquals(
            $this->paymentMock,
            $this->removeCaptureCommentsPlugin->aroundCapture(
                $this->subjectMock,
                $closure,
                $this->paymentMock,
                $invoice
            )
        );
    }

    /**
     * Test for aroundCapture() method if payment method is not 'companycredit'.
     *
     * @return void
     */
    public function testAroundCaptureIfMethodNotCompanyCredit()
    {
        $invoice = null;
        $this->paymentMock->expects($this->atLeastOnce())->method('getMethod')->willReturn('dummy method');
        $this->paymentMock->expects($this->never())->method('getOrder');

        $closure = function ($paymentMock) {
            return $paymentMock;
        };

        $this->assertEquals(
            $this->paymentMock,
            $this->removeCaptureCommentsPlugin->aroundCapture(
                $this->subjectMock,
                $closure,
                $this->paymentMock,
                $invoice
            )
        );
    }
}
