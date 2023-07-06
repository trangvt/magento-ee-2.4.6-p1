<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\History;
use Magento\CompanyCredit\Model\HistoryHydrator;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for HistoryHydrator model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryHydratorTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $objectHelper;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var HistoryHydrator
     */
    private $historyHydrator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->historyHydrator = $objectManager->getObject(
            HistoryHydrator::class,
            [
                'userContext' => $this->userContext,
                'priceCurrency' => $this->priceCurrency,
                'objectHelper' => $this->objectHelper,
                'serializer' => $this->serializer
            ]
        );
    }

    /**
     * Data provider for testLogCredit.
     *
     * @return array
     */
    public function hydrateProvider()
    {
        return [
            ['USD', 'USD', 1, 1, 1, UserContextInterface::USER_TYPE_CUSTOMER, 1, 0, 1],
            ['EUR', 'EUR', 1.3, 0, 0, 0, 1, 1, 0],
            [null, 'USD', 1, 1, 0, 0, 2, 1, 0],
        ];
    }

    /**
     * Test for method logCredit.
     *
     * @param string $currencyCode
     * @param string $currencyOperation
     * @param float|int $currencyRate
     * @param int $currencyRateCalls
     * @param int $userId
     * @param int $userType
     * @param int $currencyCodeCalls
     * @param int $userContextCalls
     * @param int $userPreset
     * @return void
     * @dataProvider hydrateProvider
     */
    public function testHydrate(
        $currencyCode,
        $currencyOperation,
        $currencyRate,
        $currencyRateCalls,
        $userId,
        $userType,
        $currencyCodeCalls,
        $userContextCalls,
        $userPreset
    ) {
        $data = [
            'status' =>  HistoryInterface::TYPE_PURCHASED,
            'amount' => 10,
            'currency' => $currencyCode,
            'comment' => 'Some comment',
            'systemComments' => ['order' => '00001'],
            'purchaseOrder' => 'O123',
        ];
        $data['options'] = new DataObject(
            [
                'purchaseOrder' => 'O123',
                'order_increment' => '00001',
                'currency_display' => 'RUB',
                'currency_base' => 'EUR'
            ]
        );
        $creditId = 1;
        $creditBalance = -15;
        $creditLimit = 75;
        $availableLimit = 60;
        $creditCurrency = 'USD';
        $credit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $credit->expects($this->once())->method('getId')->willReturn($creditId);
        $credit->expects($this->once())->method('getBalance')->willReturn($creditBalance);
        $credit->expects($this->once())->method('getCreditLimit')->willReturn($creditLimit);
        $credit->expects($this->once())->method('getAvailableLimit')->willReturn($availableLimit);
        $credit->expects($this->exactly($currencyCodeCalls))->method('getCurrencyCode')->willReturn($creditCurrency);
        $history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectHelper->expects($this->once())->method('populateWithArray')
            ->with($history, $data['options']->getData(), HistoryInterface::class)
            ->willReturnSelf();
        $history->expects($this->once())->method('unsetData')
            ->with(HistoryInterface::HISTORY_ID)->willReturnSelf();
        $history->expects($this->once())->method('setCompanyCreditId')->with($creditId)->willReturnSelf();
        $history->expects($this->once())->method('setBalance')->with($creditBalance)->willReturnSelf();
        $history->expects($this->once())->method('setCreditLimit')->with($creditLimit)->willReturnSelf();
        $history->expects($this->once())->method('setAvailableLimit')->with($availableLimit)->willReturnSelf();
        $history->expects($this->once())->method('setCurrencyCredit')->with($creditCurrency)->willReturnSelf();
        $history->expects($this->once())->method('setType')->with($data['status'])->willReturnSelf();
        $history->expects($this->once())->method('setAmount')->with($data['amount'])->willReturnSelf();
        $history->expects($this->once())->method('setCurrencyOperation')
            ->with($data['options']->getData('currency_display'))->willReturnSelf();
        $this->serializer->expects($this->once())->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $history->expects($this->once())->method('setComment')
            ->with(json_encode(['custom' => $data['comment']] + ['system' => $data['systemComments']]))
            ->willReturnSelf();
        $history->expects($this->exactly($userContextCalls))->method('setUserId')->with($userId)->willReturnSelf();
        $history->expects($this->exactly($userContextCalls))->method('setUserType')->with($userType)->willReturnSelf();
        $history->expects($this->once())->method('getUserId')->willReturn(1);
        $history->expects($this->once())->method('getUserType')->willReturn($userPreset);
        $history->expects($this->once())->method('setRate')->with(1)->willReturnSelf();
        $history->expects($this->once())->method('setRateCredit')->with($currencyRate)->willReturnSelf();
        $history->expects($this->once())->method('getCurrencyCredit')->willReturn($creditCurrency);
        $history->expects($this->once())->method('getCurrencyOperation')->willReturn($currencyOperation);
        $this->userContext->expects($this->exactly($userContextCalls))->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->exactly($userContextCalls))->method('getUserType')->willReturn($userType);
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCurrency->expects($this->exactly(1 + $currencyRateCalls))
            ->method('getCurrency')->with(null, $data['options']->getCurrencyBase())->willReturn($currency);
        $currency->expects($this->exactly(1 + $currencyRateCalls))->method('getRate')
            ->with($creditCurrency)->willReturn($currencyRate);
        $this->historyHydrator->hydrate(
            $history,
            $credit,
            $data['status'],
            $data['amount'],
            $data['currency'],
            $data['comment'],
            $data['systemComments'],
            $data['options']
        );
    }
}
