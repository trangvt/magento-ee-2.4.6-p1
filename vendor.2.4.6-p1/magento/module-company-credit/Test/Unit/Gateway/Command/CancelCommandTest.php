<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Gateway\Command;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Gateway\Command\CancelCommand;
use Magento\CompanyCredit\Model\CreditBalance;
use Magento\Directory\Model\Currency;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for CancelCommand with order cancellation action, revert credit to company.
 */
class CancelCommandTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManager;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReader;

    /**
     * @var CreditBalance|MockObject
     */
    private $creditBalance;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var CancelCommand
     */
    private $cancelCommand;

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
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManagerHelper($this);
        $this->subjectReader = $this->objectManager->getObject(
            SubjectReader::class
        );
        $this->cancelCommand = $this->objectManager->getObject(
            CancelCommand::class,
            [
                'creditBalance' => $this->creditBalance,
                'companyManagement' => $this->companyManagement,
                'subjectReader' => $this->subjectReader
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @param int $calls
     * @param int $companyId
     * @param bool $isCreditIncreased
     * @param Phrase $message
     * @return void
     * @dataProvider testExecuteDataProvider
     */
    public function testExecute($calls, $companyId, $isCreditIncreased, $message)
    {
        $customerId = 1;
        $price = 33;
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->setMethods(['formatTxt'])
            ->getMock();
        $currency->expects($this->exactly($calls))
            ->method('formatTxt')
            ->willReturn((string)$price);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'getBaseCurrency', 'getBaseGrandTotal', 'addStatusHistoryComment'])
            ->getMock();
        $order->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $order->expects($this->exactly($calls))
            ->method('getBaseGrandTotal')
            ->willReturn($price);
        $order->expects($this->exactly($calls))
            ->method('getBaseCurrency')
            ->willReturn($currency);
        $order->expects($this->once())
            ->method('addStatusHistoryComment')
            ->with($message, Order::STATE_CANCELED)
            ->willReturn(true);
        $paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMockForAbstractClass();
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $paymentDataObject = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $paymentDataObject->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $companyMock->expects($this->once())
            ->method('getId')
            ->willReturn($companyId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($companyMock);
        $this->creditBalance->expects($this->exactly($calls))
            ->method('cancel')
            ->with($order)
            ->willReturn($isCreditIncreased);
        $subject = ['payment' => $paymentDataObject];

        $this->cancelCommand->execute($subject);
    }

    /**
     * Data provider for testExecute method.
     *
     * @return array
     */
    public function testExecuteDataProvider()
    {
        return [
            [
                'calls' => 1,
                'companyId' => 1,
                'creditBalance' => true,
                'message' => __('Order is canceled. We reverted %1 to the company credit.', 33),
            ],
            [
                'calls' => 1,
                'companyId' => 1,
                'creditBalance' => false,
                'message' => __('Order is canceled. The order amount is not reverted to the company credit.'),
            ],
            [
                'calls' => 0,
                'companyId' => 0,
                'creditBalance' => false,
                'message' => __(
                    'Order is cancelled. The order amount is not reverted to the company credit '
                    . 'because the company to which this customer belongs does not exist.'
                ),
            ],
        ];
    }

    /**
     * Test for execute method with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Order Payment should be provided');
        $paymentDataObject = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $paymentDataObject->expects($this->once())
            ->method('getPayment')
            ->willReturn(false);
        $subject = ['payment' => $paymentDataObject];

        $this->cancelCommand->execute($subject);
    }
}
