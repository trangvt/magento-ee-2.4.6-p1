<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Gateway\Command;

use Magento\CompanyCredit\Gateway\Command\RefundCommand;
use Magento\CompanyCredit\Model\CreditBalance;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for refund command gateway command.
 */
class RefundCommandTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var RefundCommand
     */
    private $refundCommand;

    /**
     * @var CreditBalance|MockObject
     */
    private $creditBalance;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditBalance = $this->getMockBuilder(CreditBalance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $subjectReader = $this->objectManagerHelper->getObject(
            SubjectReader::class
        );
        $this->refundCommand = $this->objectManagerHelper->getObject(
            RefundCommand::class,
            [
                'creditBalance' => $this->creditBalance,
                'subjectReader' => $subjectReader
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $commandSubject = ['payment' => $paymentDataObjectMock];
        $paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getOrder',
                'getCreditmemo',
            ])
            ->getMockForAbstractClass();
        $paymentDataObjectMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'setState'])
            ->getMock();
        $paymentMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditmemo = $this->getMockBuilder(CreditmemoInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $paymentMock->expects($this->once())->method('getCreditmemo')->willReturn($creditmemo);

        $this->creditBalance->expects($this->once())->method('refund')
            ->with($orderMock, $creditmemo);

        $this->refundCommand->execute($commandSubject);
    }

    /**
     * Test for execute method with LogicException.
     *
     * @return void
     */
    public function testExecuteWithLogicException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Order Payment should be provided');
        $paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $commandSubject = ['payment' => $paymentDataObjectMock];
        $paymentDataObjectMock->expects($this->once())->method('getPayment')->willReturn([]);

        $this->refundCommand->execute($commandSubject);
    }
}
