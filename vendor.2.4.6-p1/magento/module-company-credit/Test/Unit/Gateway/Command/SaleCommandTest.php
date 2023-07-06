<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Gateway\Command;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Gateway\Command\SaleCommand;
use Magento\CompanyCredit\Model\CreditBalance;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SaleCommand gateway model.
 */
class SaleCommandTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SaleCommand
     */
    private $saleCommand;

    /**
     * @var ConfigInterface|MockObject
     */
    private $configInterfaceMock;

    /**
     * @var CreditBalance|MockObject
     */
    private $creditBalanceMock;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagementMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditBalanceMock = $this->getMockBuilder(CreditBalance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $subjectReader = $this->objectManagerHelper->getObject(
            SubjectReader::class
        );
        $this->saleCommand = $this->objectManagerHelper->getObject(
            SaleCommand::class,
            [
                'configInterface' => $this->configInterfaceMock,
                'creditBalance' => $this->creditBalanceMock,
                'companyManagement' => $this->companyManagementMock,
                'subjectReader' => $subjectReader
            ]
        );
    }

    /**
     * Test for execute() method.
     *
     * @return void
     */
    public function testExecute()
    {
        $customerId = 1;
        $companyId = 1;
        $companyName = 'Company Name';
        $poNumber = '001';

        $paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $commandSubject = ['payment' => $paymentDataObjectMock];
        $paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setSkipOrderProcessing',
                'getOrder',
                'setAdditionalInformation',
                'getPoNumber'
            ])
            ->getMockForAbstractClass();
        $paymentDataObjectMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'setState'])
            ->getMock();
        $paymentMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagementMock->expects($this->once())->method('getByCustomerId')->with($customerId)
            ->willReturn($companyMock);
        $this->configInterfaceMock->expects($this->once())->method('getValue')->with('order_status')
            ->willReturn('some_status');
        $orderMock->expects($this->once())->method('setState')->with(Order::STATE_NEW);
        $paymentMock->expects($this->once())->method('setSkipOrderProcessing')->with(true);
        $companyMock->expects($this->any())->method('getId')->willReturn($companyId);
        $companyMock->expects($this->any())->method('getCompanyName')->willReturn($companyName);
        $paymentMock->expects($this->exactly(2))->method('setAdditionalInformation')->withConsecutive(
            ['company_id', $companyId],
            ['company_name', $companyName]
        );
        $this->creditBalanceMock->expects($this->once())->method('decreaseBalanceByOrder')
            ->with($orderMock, $poNumber);
        $paymentMock->expects($this->once())->method('getPoNumber')->willReturn($poNumber);

        $this->saleCommand->execute($commandSubject);
    }

    /**
     * Test for execute() method with LogicException.
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

        $this->saleCommand->execute($commandSubject);
    }
}
