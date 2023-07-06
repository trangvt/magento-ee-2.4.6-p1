<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Observer;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Observer\InvoiceRegisterObserver;
use Magento\Directory\Model\Currency;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 *  Unit test for observer registration invoice.
 */
class InvoiceRegisterObserverTest extends TestCase
{
    /**
     * @var InvoiceRegisterObserver
     */
    private $invoiceRegisterObserver;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Invoice|MockObject
     */
    private $invoice;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getOrder', 'getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoice = $this->createMock(Invoice::class);
        $this->order = $this->createMock(Order::class);
        $payment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $currency = $this->createMock(Currency::class);
        $currency->expects($this->once())->method('formatTxt')->willReturnArgument(0);
        $this->order->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $this->order->expects($this->once())->method('getPayment')->willReturn($payment);
        $payment->expects($this->once())->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);

        $this->observer->expects($this->once())->method('getOrder')->willReturn($this->order);
        $this->observer->expects($this->once())->method('getInvoice')->willReturn($this->invoice);

        $objectManager = new ObjectManager($this);
        $this->invoiceRegisterObserver = $objectManager->getObject(
            InvoiceRegisterObserver::class
        );
    }

    /**
     * Test method for execute.
     *
     * @return void
     */
    public function testExecuteWithStatus()
    {
        $this->order->expects($this->once())->method('getStatus')->willReturn('new');
        $this->order->expects($this->once())->method('addStatusHistoryComment');
        $this->order->expects($this->never())->method('setCustomerNote');

        $this->invoiceRegisterObserver->execute($this->observer);
    }

    /**
     * Test method for execute.
     *
     * @return void
     */
    public function testExecuteWithoutStatus()
    {
        $this->order->expects($this->once())->method('getStatus')->willReturn(null);
        $this->order->expects($this->never())->method('addStatusHistoryComment');
        $this->order->expects($this->once())->method('setCustomerNote');

        $this->invoiceRegisterObserver->execute($this->observer);
    }
}
