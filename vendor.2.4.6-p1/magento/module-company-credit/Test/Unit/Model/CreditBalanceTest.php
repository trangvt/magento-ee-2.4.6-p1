<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Api\CreditBalanceManagementInterface;
use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\CompanyOrder;
use Magento\CompanyCredit\Model\CompanyStatus;
use Magento\CompanyCredit\Model\CreditBalance;
use Magento\CompanyCredit\Model\CreditBalanceOptions;
use Magento\CompanyCredit\Model\CreditBalanceOptionsFactory;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\CreditmemoCommentInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreditBalance model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditBalanceTest extends TestCase
{
    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagement;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var CompanyStatus|MockObject
     */
    private $companyStatus;

    /**
     * @var CreditBalanceManagementInterface|MockObject
     */
    private $creditBalanceManagement;

    /**
     * @var CompanyOrder|MockObject
     */
    private $companyOrder;

    /**
     * @var CreditBalanceOptionsFactory|MockObject
     */
    private $creditBalanceOptionsFactory;

    /**
     * @var CreditBalance
     */
    private $creditBalance;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitManagement = $this->createMock(
            CreditLimitManagementInterface::class
        );
        $this->priceCurrency = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->companyStatus = $this->createMock(
            CompanyStatus::class
        );
        $this->creditBalanceManagement = $this->createMock(
            CreditBalanceManagementInterface::class
        );
        $this->companyOrder = $this->createMock(
            CompanyOrder::class
        );
        $this->creditBalanceOptionsFactory = $this->getMockBuilder(CreditBalanceOptionsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->creditBalance = $objectManager->getObject(
            CreditBalance::class,
            [
                'creditLimitManagement' => $this->creditLimitManagement,
                'priceCurrency' => $this->priceCurrency,
                'companyStatus' => $this->companyStatus,
                'creditBalanceManagement' => $this->creditBalanceManagement,
                'companyOrder' => $this->companyOrder,
                'creditBalanceOptionsFactory' => $this->creditBalanceOptionsFactory
            ]
        );
    }

    /**
     * Test for method decreaseBalanceByOrder.
     *
     * @return void
     */
    public function testDecreaseBalanceByOrder()
    {
        $companyId = 2;
        $creditLimitId = 3;
        $orderId = 'O001';
        $poNumber = 'PO-001';
        $orderTotal = 12.5;
        $rate = 1.4;
        $orderCurrency = 'EUR';
        $baseCurrency = 'RUB';
        $creditCurrency = 'USD';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $creditLimit = $this->getMockForAbstractClass(CreditLimitInterface::class);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->exactly(2))->method('getId')->willReturn($creditLimitId);
        $creditLimit->expects($this->once())->method('getExceedLimit')->willReturn(false);
        $order->expects($this->exactly(2))->method('getBaseGrandTotal')->willReturn($orderTotal);
        $order->expects($this->exactly(3))->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $order->expects($this->exactly(2))->method('getOrderCurrencyCode')->willReturn($orderCurrency);
        $currency = $this->createMock(Currency::class);
        $creditLimit->expects($this->atLeastOnce())->method('getCurrencyCode')->willReturn($creditCurrency);
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')->with(true, $baseCurrency)->willReturn($currency);
        $currency->expects($this->once())->method('getRate')->with($creditCurrency)->willReturn($rate);
        $currency->expects($this->once())
            ->method('convert')->with($orderTotal, $creditCurrency)->willReturn($orderTotal * $rate);
        $creditLimit->expects($this->once())->method('getAvailableLimit')->willReturn(100);
        $order->expects($this->once())->method('getIncrementId')->willReturn($orderId);
        $creditBalanceOptions = $this->getMockBuilder(CreditBalanceOptions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditBalanceOptionsFactory->expects($this->any())->method('create')
            ->willReturn($creditBalanceOptions);
        $creditBalanceOptions->expects($this->exactly(5))->method('setData')->withConsecutive(
            ['purchase_order', $poNumber],
            ['custom_reference_number', $poNumber],
            ['order_increment', $orderId],
            ['currency_display', $orderCurrency],
            ['currency_base', $baseCurrency]
        );

        $this->creditBalanceManagement->expects($this->once())->method('decrease')
            ->with(
                $creditLimitId,
                $orderTotal,
                $baseCurrency,
                HistoryInterface::TYPE_PURCHASED,
                '',
                $creditBalanceOptions
            );
        $this->creditBalance->decreaseBalanceByOrder($order, $poNumber);
    }

    /**
     * Test for method decreaseBalanceByOrder with exception about unavailable payment method.
     *
     * @return void
     */
    public function testDecreaseBalanceByOrderWithExceptionAboutUnavailableMethod()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('The requested Payment Method is not available.');
        $companyId = 2;
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn(null);
        $this->creditBalance->decreaseBalanceByOrder($order);
    }

    /**
     * Test for method decreaseBalanceByOrder with exception about exceeded limit.
     *
     * @return void
     */
    public function testDecreaseBalanceByOrderWithExceptionAboutExceededLimit()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage(
            'Payment On Account cannot be used for this order because your order amount exceeds your credit amount.'
        );
        $companyId = 2;
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn(1);
        $creditLimit->expects($this->once())->method('getExceedlimit')->willReturn(false);
        $order->expects($this->once())->method('getBaseGrandTotal')->willReturn(5);
        $order->expects($this->once())->method('getOrderCurrencyCode')->willReturn('USD');
        $creditLimit->expects($this->once())->method('getCurrencyCode')->willReturn('USD');
        $creditLimit->expects($this->once())->method('getAvailableLimit')->willReturn(4);

        $this->creditBalance->decreaseBalanceByOrder($order);
    }

    /**
     * Test for method increaseBalanceByOrder.
     *
     * @return void
     */
    public function testIncreaseBalanceByOrder()
    {
        $companyId = 2;
        $creditLimitId = 3;
        $orderTotal = 12.5;
        $orderCurrency = 'USD';
        $baseCurrency = 'RUB';
        $orderId = 'O001';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $creditLimit = $this->getMockForAbstractClass(CreditLimitInterface::class);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditLimitId);
        $order->expects($this->once())->method('getBaseGrandTotal')->willReturn($orderTotal);
        $order->expects($this->exactly(2))->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $order->expects($this->once())->method('getOrderCurrencyCode')->willReturn($orderCurrency);
        $order->expects($this->once())->method('getIncrementId')->willReturn($orderId);
        $creditBalanceOptions = $this->prepareCreditBalanceOptionsMock($orderId, $orderCurrency, $baseCurrency);
        $this->creditBalanceManagement->expects($this->once())->method('increase')
            ->with(
                $creditLimitId,
                $orderTotal,
                $baseCurrency,
                HistoryInterface::TYPE_REVERTED,
                '',
                $creditBalanceOptions
            );

        $this->creditBalance->increaseBalanceByOrder($order);
    }

    /**
     * Test for method cancel without company.
     *
     * @return void
     */
    public function testCancelWithoutCompany()
    {
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdByOrder')->with($order)->willReturn(null);

        $this->creditBalanceManagement->expects($this->never())->method('increase');

        $this->assertFalse($this->creditBalance->cancel($order));
    }

    /**
     * Test for method cancel with company.
     *
     * @return void
     */
    public function testCancel()
    {
        $companyId = 2;
        $creditLimitId = 3;
        $orderTotal = 12.5;
        $orderCurrency = 'USD';
        $baseCurrency = 'RUB';
        $orderId = 'O001';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->companyStatus->expects($this->once())
            ->method('isRevertAvailable')->with($companyId)->willReturn(true);
        $this->companyOrder->expects($this->exactly(2))
            ->method('getCompanyIdByOrder')->with($order)->willReturn($companyId);
        $creditLimit = $this->getMockForAbstractClass(CreditLimitInterface::class);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditLimitId);
        $order->expects($this->once())->method('getBaseGrandTotal')->willReturn($orderTotal);
        $order->expects($this->exactly(2))->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $order->expects($this->once())->method('getOrderCurrencyCode')->willReturn($orderCurrency);
        $order->expects($this->once())->method('getIncrementId')->willReturn($orderId);
        $creditBalanceOptions = $this->prepareCreditBalanceOptionsMock($orderId, $orderCurrency, $baseCurrency);
        $this->creditBalanceManagement->expects($this->once())->method('increase')
            ->with(
                $creditLimitId,
                $orderTotal,
                $baseCurrency,
                HistoryInterface::TYPE_REVERTED,
                '',
                $creditBalanceOptions
            );

        $this->creditBalance->cancel($order);
    }

    /**
     * Test for method refund.
     *
     * @return void
     */
    public function testRefund()
    {
        $companyId = 1;
        $creditLimitId = 2;
        $creditmemoTotal = 15.5;
        $creditmemoCurrency = 'USD';
        $orderId = '001';
        $commentText = 'Refund Comment';
        $orderCurrency = 'USD';
        $baseCurrency = 'RUB';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $creditmemo = $this->getMockForAbstractClass(CreditmemoInterface::class);
        $this->companyOrder->expects($this->once())
            ->method('getCompanyIdForRefund')->with($order)->willReturn($companyId);
        $creditLimit = $this->getMockForAbstractClass(CreditLimitInterface::class);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with($companyId)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditLimitId);
        $creditmemo->expects($this->once())->method('getBaseGrandTotal')->willReturn($creditmemoTotal);
        $this->priceCurrency->expects($this->once())
            ->method('round')->with($creditmemoTotal)->willReturn($creditmemoTotal);
        $creditmemo->expects($this->once())->method('getBaseCurrencyCode')->willReturn($creditmemoCurrency);
        $order->expects($this->once())->method('getIncrementId')->willReturn($orderId);
        $order->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $order->expects($this->once())->method('getOrderCurrencyCode')->willReturn($orderCurrency);
        $creditmemoComment = $this->createMock(
            CreditmemoCommentInterface::class
        );
        $creditmemo->expects($this->once())->method('getComments')->willReturn([$creditmemoComment]);
        $creditmemoComment->expects($this->once())->method('getComment')->willReturn($commentText);
        $creditBalanceOptions = $this->prepareCreditBalanceOptionsMock($orderId, $orderCurrency, $baseCurrency);
        $this->creditBalanceManagement->expects($this->once())->method('increase')
            ->with(
                $creditLimitId,
                $creditmemoTotal,
                $creditmemoCurrency,
                HistoryInterface::TYPE_REFUNDED,
                $commentText,
                $creditBalanceOptions
            );
        $this->creditBalance->refund($order, $creditmemo);
    }

    /**
     * Prepare CreditBalanceOptions model mock.
     *
     * @param int $orderId
     * @param string $orderCurrency
     * @param string $baseCurrency
     * @return MockObject
     */
    private function prepareCreditBalanceOptionsMock($orderId, $orderCurrency, $baseCurrency)
    {
        $creditBalanceOptions = $this->getMockBuilder(CreditBalanceOptions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditBalanceOptionsFactory->expects($this->any())->method('create')
            ->willReturn($creditBalanceOptions);
        $creditBalanceOptions->expects($this->exactly(3))->method('setData')->withConsecutive(
            ['order_increment', $orderId],
            ['currency_display', $orderCurrency],
            ['currency_base', $baseCurrency]
        );

        return $creditBalanceOptions;
    }
}
