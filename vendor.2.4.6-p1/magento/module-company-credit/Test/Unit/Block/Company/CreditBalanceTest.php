<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Block\Company;

use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Block\Company\CreditBalance;
use Magento\CompanyCredit\Model\CreditDetails\CustomerProvider;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditBalanceTest extends TestCase
{
    /**
     * @var CustomerProvider|MockObject
     */
    private $customerProvider;

    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceFormatter;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

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
        $this->customerProvider = $this->createMock(
            CustomerProvider::class
        );
        $this->creditDataProvider = $this->createMock(
            CreditDataProviderInterface::class
        );
        $this->priceFormatter = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->websiteCurrency = $this->createMock(
            WebsiteCurrency::class
        );

        $objectManager = new ObjectManager($this);
        $this->creditBalance = $objectManager->getObject(
            CreditBalance::class,
            [
                'customerProvider' => $this->customerProvider,
                'creditDataProvider' => $this->creditDataProvider,
                'priceFormatter' => $this->priceFormatter,
                'websiteCurrency' => $this->websiteCurrency,
            ]
        );
    }

    /**
     * Test for isOutstandingBalanceNegative method.
     *
     * @return void
     */
    public function testIsOutstandingBalanceNegative()
    {
        $companyId = 1;
        $creditData = $this->createMock(
            CreditDataInterface::class
        );
        $this->customerProvider->expects($this->exactly(2))->method('getCurrentUserCredit')->willReturn($creditData);
        $creditData->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($creditData);
        $creditData->expects($this->once())->method('getBalance')->willReturn(-30);
        $this->assertTrue($this->creditBalance->isOutstandingBalanceNegative());
    }

    /**
     * Test for getOutstandingBalance method.
     *
     * @return void
     */
    public function testGetOutstandingBalance()
    {
        $this->assertEquals($this->mockCreditData('getBalance'), $this->creditBalance->getOutstandingBalance());
    }

    /**
     * Test for getAvailableCredit method.
     *
     * @return void
     */
    public function testGetAvailableCredit()
    {
        $this->assertEquals($this->mockCreditData('getAvailableLimit'), $this->creditBalance->getAvailableCredit());
    }

    /**
     * Test for getCreditLimit method.
     *
     * @return void
     */
    public function testGetCreditLimit()
    {
        $this->assertEquals($this->mockCreditData('getCreditLimit'), $this->creditBalance->getCreditLimit());
    }

    /**
     * Mock credit data.
     *
     * @param string $method
     * @return MockObject
     */
    private function mockCreditData($method)
    {
        $companyId = 1;
        $amount = 100;
        $creditCurrency = 'USD';
        $expectedResult = sprintf('$%.2f', $amount);
        $creditData = $this->createMock(
            CreditDataInterface::class
        );
        $this->customerProvider->expects($this->exactly(2))->method('getCurrentUserCredit')->willReturn($creditData);
        $creditData->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($creditData);
        $creditData->expects($this->once())->method($method)->willReturn($amount);
        $creditData->expects($this->once())->method('getCurrencyCode')->willReturn($creditCurrency);
        $currency = $this->createMock(Currency::class);
        $this->websiteCurrency->expects($this->once())
            ->method('getCurrencyByCode')->with($creditCurrency)->willReturn($currency);
        $this->priceFormatter->expects($this->once())->method('format')->with(
            $amount,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currency
        )->willReturn($expectedResult);
        return $expectedResult;
    }
}
