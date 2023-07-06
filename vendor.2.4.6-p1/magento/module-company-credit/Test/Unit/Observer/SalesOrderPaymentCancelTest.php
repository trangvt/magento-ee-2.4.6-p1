<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Observer;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Observer\SalesOrderPaymentCancel;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\CompanyCredit\Observer\SalesOrderPaymentCancel.
 */
class SalesOrderPaymentCancelTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SalesOrderPaymentCancel
     */
    private $salesOrderPaymentCancel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->salesOrderPaymentCancel = $this->objectManagerHelper->getObject(
            SalesOrderPaymentCancel::class
        );
    }

    /**
     * Test execute with any other payments.
     *
     * @return void
     */
    public function testExecuteWithOtherPayments()
    {
        $paymentMethodInstanceMock = $this->getMockBuilder(MethodInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $observerMock = $this->buildObserverMock($paymentMethodInstanceMock);

        $paymentMethodInstanceMock->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn('another_payment_method');

        $paymentMethodInstanceMock->expects($this->never())
            ->method('cancel');

        $this->salesOrderPaymentCancel->execute($observerMock);
    }

    /**
     * Test execute with company payments.
     *
     * @return void
     */
    public function testExecute()
    {
        $paymentMethodInstanceMock = $this->getMockBuilder(MethodInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $observerMock = $this->buildObserverMock($paymentMethodInstanceMock);

        $paymentMethodInstanceMock->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);

        $paymentMethodInstanceMock->expects($this->once())
            ->method('cancel');

        $this->salesOrderPaymentCancel->execute($observerMock);
    }

    /**
     * Build observer mock.
     *
     * @param MockObject $paymentMethodInstanceMock
     * @return MockObject
     */
    private function buildObserverMock(MockObject $paymentMethodInstanceMock)
    {
        $paymentMock = $this->getMockBuilder(InfoInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $paymentMock->expects($this->atLeastOnce())
            ->method('getMethodInstance')
            ->willReturn($paymentMethodInstanceMock);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentMock);

        return $observerMock;
    }
}
