<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\CreditBalanceManagement;
use Magento\CompanyCredit\Model\CreditBalanceOptions;
use Magento\CompanyCredit\Model\CreditLimitHistory;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\ResourceModel\CreditLimit;
use Magento\CompanyCredit\Model\ResourceModel\History\Collection as HistoryCollection;
use Magento\CompanyCredit\Model\ResourceModel\History\CollectionFactory as HistoryCollectionFactory;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreditBalanceManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditBalanceManagementTest extends TestCase
{
    /**
     * @var CreditLimitRepositoryInterface|MockObject
     */
    private $creditLimitRepository;

    /**
     * @var CreditLimitHistory|MockObject
     */
    private $creditLimitHistory;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var CreditLimit|MockObject
     */
    private $creditLimitResource;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var HistoryCollectionFactory|MockObject
     */
    private $historyCollectionFactoryMock;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrencyMock;

    /**
     * @var CreditBalanceManagement
     */
    private $creditBalanceManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitRepository = $this
            ->getMockBuilder(CreditLimitRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitHistory = $this->getMockBuilder(CreditLimitHistory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitResource = $this
            ->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->historyCollectionFactoryMock = $this->getMockBuilder(HistoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->websiteCurrencyMock = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->creditBalanceManagement = $objectManager->getObject(
            CreditBalanceManagement::class,
            [
                'creditLimitRepository' => $this->creditLimitRepository,
                'creditLimitHistory' => $this->creditLimitHistory,
                'priceCurrency' => $this->priceCurrency,
                'creditLimitResource' => $this->creditLimitResource,
                'customerRepository' => $this->customerRepository,
                'websiteCurrency' => $this->websiteCurrencyMock,
                'historyCollectionFactory' => $this->historyCollectionFactoryMock
            ]
        );
    }

    /**
     * Test for method decrease.
     *
     * @return void
     */
    public function testDecrease()
    {
        $data = [
            'balanceId' => 1,
            'status' =>  HistoryInterface::TYPE_PURCHASED,
            'amount' => 10,
            'currency' => 'USD',
            'comment' => 'Some comment',
            'orderNumber' => '00001',
            'purchaseOrder' => 'O123',
        ];
        $options = $this->getMockBuilder(CreditBalanceOptions::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $data['options'] = $options;
        $options->expects($this->atLeastOnce())->method('getData')->with('order_increment')->willReturn('00001');
        $this->creditLimitResource->expects($this->once())
            ->method('changeBalance')->with($data['balanceId'], -$data['amount'], $data['currency']);
        $credit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitRepository->expects($this->once())->method('get')
            ->with($data['balanceId'], true)->willReturn($credit);
        $this->creditLimitHistory->expects($this->once())->method('logCredit')->with(
            $credit,
            $data['status'],
            -$data['amount'],
            $data['currency'],
            $data['comment'],
            ['order' => $data['orderNumber']],
            $data['options']
        );
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $connection->expects($this->once())->method('commit');
        $this->creditLimitResource->expects($this->once())->method('getConnection')->willReturn($connection);
        $this->websiteCurrencyMock->expects($this->once())->method('isCreditCurrencyEnabled')
            ->with($data['currency'])->willReturn(true);

        $this->creditBalanceManagement->decrease(
            $data['balanceId'],
            $data['amount'],
            $data['currency'],
            $data['status'],
            $data['comment'],
            $data['options']
        );
    }

    /**
     * Test for method increase.
     *
     * @return void
     */
    public function testIncrease()
    {
        $data = [
            'balanceId' => 1,
            'status' =>  HistoryInterface::TYPE_REFUNDED,
            'amount' => 10,
            'currency' => 'USD',
            'comment' => 'Some comment',
            'orderNumber' => '00001',
            'purchaseOrder' => 'O123',
        ];
        $options = $this->getMockBuilder(CreditBalanceOptions::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $data['options'] = $options;
        $options->expects($this->atLeastOnce())->method('getData')->with('order_increment')->willReturn('00001');
        $this->creditLimitResource->expects($this->once())
            ->method('changeBalance')->with($data['balanceId'], $data['amount'], $data['currency']);
        $credit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitRepository->expects($this->once())->method('get')
            ->with($data['balanceId'], true)->willReturn($credit);
        $this->creditLimitHistory->expects($this->once())->method('logCredit')->with(
            $credit,
            $data['status'],
            $data['amount'],
            $data['currency'],
            $data['comment'],
            ['order' => $data['orderNumber']],
            $data['options']
        );
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $connection->expects($this->once())->method('commit');
        $this->creditLimitResource->expects($this->once())->method('getConnection')->willReturn($connection);
        $this->websiteCurrencyMock->expects($this->once())->method('isCreditCurrencyEnabled')
            ->with($data['currency'])->willReturn(true);

        $this->creditBalanceManagement->increase(
            $data['balanceId'],
            $data['amount'],
            $data['currency'],
            $data['status'],
            $data['comment'],
            $data['options']
        );
    }

    /**
     * Test for method increase.
     *
     * @return void
     */
    public function testIncreaseWithAllocatedStatus()
    {
        $data = [
            'balanceId' => 1,
            'status' =>  HistoryInterface::TYPE_ALLOCATED,
            'amount' => 10,
            'currency' => 'USD',
            'comment' => 'Some comment',
            'orderNumber' => '00001',
            'purchaseOrder' => 'O123',
        ];
        $options = $this->getMockBuilder(CreditBalanceOptions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data['options'] = $options;
        $creditCurrencyCode = 'EUR';
        $creditCurrencyRate = 2;
        $creditLimitValue = 100;
        $creditLimitValueToSet = $creditLimitValue + $data['amount'] * 2;

        $this->prepareHistoryCollectionMock($data['balanceId'], 0);
        $creditMock = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrencyCode', 'getCreditLimit', 'setCreditLimit', 'setData'])
            ->getMockForAbstractClass();
        $this->creditLimitRepository->expects($this->once())->method('get')
            ->with($data['balanceId'])->willReturn($creditMock);
        $creditMock->expects($this->exactly(2))->method('getCurrencyCode')->willReturn($creditCurrencyCode);
        $currencyMock = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRate'])
            ->getMock();
        $this->priceCurrency->expects($this->once())->method('getCurrency')->with(null, $creditCurrencyCode)
            ->willReturn($currencyMock);
        $currencyMock->expects($this->once())->method('getRate')->willReturn($creditCurrencyRate);
        $creditMock->expects($this->once())->method('getCreditLimit')->willReturn($creditLimitValue);
        $creditMock->expects($this->once())->method('setCreditLimit')->with($creditLimitValueToSet);
        $creditMock->expects($this->once())->method('setData')->with('credit_comment', $data['comment']);
        $this->creditLimitRepository->expects($this->once())->method('save')->with($creditMock);
        $this->websiteCurrencyMock->expects($this->once())->method('isCreditCurrencyEnabled')
            ->with($data['currency'])->willReturn(true);

        $this->creditBalanceManagement->increase(
            $data['balanceId'],
            $data['amount'],
            $data['currency'],
            $data['status'],
            $data['comment'],
            $data['options']
        );
    }

    /**
     * Test for method increase with rollBack transaction.
     *
     * @return void
     */
    public function testIncreaseWithRollBack()
    {
        $this->expectException('Exception');
        $data = [
            'balanceId' => 1,
            'status' =>  HistoryInterface::TYPE_REFUNDED,
            'amount' => 10,
            'currency' => 'USD',
            'comment' => 'Some comment',
        ];
        $this->creditLimitResource->expects($this->once())
            ->method('changeBalance')->with($data['balanceId'], $data['amount'], $data['currency'])
            ->willThrowException(new \Exception());
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $connection->expects($this->once())->method('rollBack');
        $this->creditLimitResource->expects($this->once())->method('getConnection')->willReturn($connection);
        $this->websiteCurrencyMock->expects($this->once())->method('isCreditCurrencyEnabled')
            ->with($data['currency'])->willReturn(true);

        $this->creditBalanceManagement->increase(
            $data['balanceId'],
            $data['amount'],
            $data['currency'],
            $data['status'],
            $data['comment']
        );
    }

    /**
     * Test for method decrease with rollBack transaction.
     *
     * @return void
     */
    public function testDecreaseWithRollBack()
    {
        $this->expectException('Exception');
        $data = [
            'balanceId' => 1,
            'status' =>  HistoryInterface::TYPE_REFUNDED,
            'amount' => 10,
            'currency' => 'USD',
            'comment' => 'Some comment',
        ];
        $this->creditLimitResource->expects($this->once())
            ->method('changeBalance')->with($data['balanceId'], $data['amount'], $data['currency'])
            ->willThrowException(new \Exception());
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $connection->expects($this->once())->method('rollBack');
        $this->creditLimitResource->expects($this->once())->method('getConnection')->willReturn($connection);
        $this->websiteCurrencyMock->expects($this->once())->method('isCreditCurrencyEnabled')
            ->with($data['currency'])->willReturn(true);

        $this->creditBalanceManagement->increase(
            $data['balanceId'],
            $data['amount'],
            $data['currency'],
            $data['status'],
            $data['comment']
        );
    }

    /**
     * Test for method increase with InputException.
     *
     * @return void
     */
    public function testIncreaseWithException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Cannot process the request. Please check the operation type and try again.');
        $creditId = 1;
        $this->prepareHistoryCollectionMock($creditId, 1);

        $this->creditBalanceManagement->increase(
            $creditId,
            10,
            null,
            HistoryInterface::TYPE_ALLOCATED,
            null,
            null
        );
    }

    /**
     * Prepare history collection mock.
     *
     * @param int $creditId
     * @param int $collectionSize
     * @return void
     */
    private function prepareHistoryCollectionMock($creditId, $collectionSize)
    {
        $historyCollectionMock = $this->getMockBuilder(HistoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyCollectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($historyCollectionMock);
        $historyCollectionMock->expects($this->once())->method('addFieldToFilter')
            ->with('company_credit_id', ['eq' => $creditId]);
        $historyCollectionMock->expects($this->once())->method('getSize')->willReturn($collectionSize);
    }

    /**
     * Test for method increase with InputException when credit balance value is incorrect.
     *
     * @return void
     */
    public function testIncreaseWithInputExceptionForInvalidBalanceValue()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Invalid attribute value. Row ID: value = -1.');
        $this->creditBalanceManagement->increase(
            1,
            -1,
            null,
            HistoryInterface::TYPE_REFUNDED,
            null,
            null
        );
    }

    /**
     * Test for method increase with InputException when credit balance ID is incorrect.
     *
     * @return void
     */
    public function testIncreaseWithInputExceptionForInvalidBalanceId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"balanceId" is required. Enter and try again.');
        $this->creditBalanceManagement->increase(
            null,
            10,
            null,
            HistoryInterface::TYPE_REFUNDED,
            null,
            null
        );
    }

    /**
     * Test for method increase with InputException when credit balance currency is incorrect.
     *
     * @return void
     */
    public function testIncreaseWithInputExceptionForInvalidBalanceCurrency()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Invalid attribute value. Row ID: currency = USD.');
        $currency = 'USD';
        $this->websiteCurrencyMock->expects($this->once())->method('isCreditCurrencyEnabled')
            ->with($currency)->willReturn(false);

        $this->creditBalanceManagement->increase(
            1,
            10,
            $currency,
            HistoryInterface::TYPE_REFUNDED,
            null,
            null
        );
    }
}
