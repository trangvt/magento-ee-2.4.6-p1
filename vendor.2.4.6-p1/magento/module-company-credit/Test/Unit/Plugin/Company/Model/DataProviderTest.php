<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Company\Model;

use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Plugin\Company\Model\DataProvider as SystemUnderTest;
use Magento\Directory\Model\Currency;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DataProvider.
 */
class DataProviderTest extends TestCase
{
    /**
     * @var SystemUnderTest
     */
    private $dataProvider;

    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Currency|MockObject
     */
    private $currencyFormatter;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditDataProvider = $this->getMockBuilder(CreditDataProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->currencyFormatter = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            SystemUnderTest::class,
            [
                'storeManager' => $this->storeManager,
                'creditDataProvider' => $this->creditDataProvider,
                'currencyFormatter' => $this->currencyFormatter
            ]
        );
    }

    /**
     * Test method for afterGetCompanyResultData.
     *
     * @return void
     */
    public function testAfterGetCompanyResultData()
    {
        $creditLimitValue = 100;
        $creditLimitFormattedValue = '100.00';
        $companyDataProvider = $this->getMockBuilder(\Magento\Company\Model\Company\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $creditData = $this->getMockBuilder(CreditDataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditData->expects($this->once())->method('getExceedLimit')->willReturn(1);
        $creditData->expects($this->atLeastOnce())->method('getCurrencyCode')->willReturn('USD');
        $creditData->expects($this->once())->method('getCreditLimit')->willReturn($creditLimitValue);
        $this->currencyFormatter->expects($this->atLeastOnce())->method('formatTxt')
            ->willReturn($creditLimitFormattedValue);
        $this->creditDataProvider->expects($this->once())->method('get')->with(1)->willReturn($creditData);
        $result = ['id' => 1];
        $expected = [
            'id' => 1,
            'company_credit' => [
                'exceed_limit' => 1,
                'currency_code' => 'USD',
                'credit_limit' => $creditLimitFormattedValue
            ]
        ];

        $this->assertEquals($expected, $this->dataProvider->afterGetCompanyResultData($companyDataProvider, $result));
    }

    /**
     * Test method for afterGetCompanyResultData.
     *
     * @return void
     */
    public function testAfterGetCompanyResultDataWithoutCurrency()
    {
        $creditLimitValue = 100;
        $creditLimitFormattedValue = '100.00';
        $companyDataProvider = $this->getMockBuilder(\Magento\Company\Model\Company\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $creditData = $this->getMockBuilder(CreditDataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditData->expects($this->once())->method('getExceedLimit')->willReturn(1);
        $creditData->expects($this->atLeastOnce())->method('getCurrencyCode')->willReturn(null);
        $creditData->expects($this->once())->method('getCreditLimit')->willReturn($creditLimitValue);
        $this->currencyFormatter->expects($this->atLeastOnce())->method('formatTxt')
            ->willReturn($creditLimitFormattedValue);
        $this->creditDataProvider->expects($this->once())->method('get')->with(1)->willReturn($creditData);
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency->expects($this->once())->method('getCurrencyCode')->willReturn('USD');
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $result = ['id' => 1];
        $expected = [
            'id' => 1,
            'company_credit' => [
                'exceed_limit' => 1,
                'currency_code' => 'USD',
                'credit_limit' => $creditLimitFormattedValue
            ]
        ];

        $this->assertEquals($expected, $this->dataProvider->afterGetCompanyResultData($companyDataProvider, $result));
    }

    /**
     * Test method for afterGetCompanyResultData.
     *
     * @return void
     */
    public function testAfterGetCompanyResultDataWithoutId()
    {
        $companyDataProvider = $this->getMockBuilder(\Magento\Company\Model\Company\DataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency->expects($this->once())->method('getCurrencyCode')->willReturn('USD');
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $result = [];
        $expected = ['company_credit' => ['currency_code' => 'USD']];

        $this->assertEquals($expected, $this->dataProvider->afterGetCompanyResultData($companyDataProvider, $result));
    }
}
