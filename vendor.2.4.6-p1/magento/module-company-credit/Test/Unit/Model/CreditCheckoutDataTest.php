<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Model\CreditCheckoutData;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CreditCheckoutData.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditCheckoutDataTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * @var CreditCheckoutData
     */
    private $creditCheckoutData;

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
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditDataProvider = $this->getMockBuilder(CreditDataProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->websiteCurrency = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->creditCheckoutData = $objectManagerHelper->getObject(
            CreditCheckoutData::class,
            [
                'userContext' => $this->userContext,
                'customerRepository' => $this->customerRepository,
                'creditDataProvider' => $this->creditDataProvider,
                'quoteRepository' => $this->quoteRepository,
                'priceCurrency' => $this->priceCurrency,
                'companyRepository' => $this->companyRepository,
                'websiteCurrency' => $this->websiteCurrency,
            ]
        );
    }

    /**
     * Test for getCurrencyConvertedRate().
     *
     * @return void
     */
    public function testGetCurrencyConvertedRate()
    {
        $baseGrandTotal = 100.00;
        $grandTotal = 90.00;
        $fromCurrencyCode = 'USD';
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getGrandTotal', 'getBaseGrandTotal', 'getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->expects($this->atLeastOnce())->method('getGrandTotal')->willReturn($baseGrandTotal);
        $toCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fromCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fromCurrency->expects($this->atLeastOnce())->method('convert')->willReturn($grandTotal);
        $this->websiteCurrency->expects($this->atLeastOnce())->method('getCurrencyByCode')
            ->willReturnOnConsecutiveCalls($fromCurrency, $toCurrency);

        $this->assertEquals(0.90, $this->creditCheckoutData->getCurrencyConvertedRate($quote, $fromCurrencyCode));
    }

    /**
     * Test for getCurrencyConvertedRate().
     *
     * @param string $currencyCode
     * @param string $quoteCurrencyCode
     * @param int $getQuoteCurrencyCodeInvokesCount
     * @return void
     * @dataProvider getCurrencyConvertedRateWithTheSameCurrenciesDataProvider
     */
    public function testGetCurrencyConvertedRateWithTheSameCurrencies(
        $currencyCode,
        $quoteCurrencyCode,
        $getQuoteCurrencyCodeInvokesCount
    ) {
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteCurrencyCode', 'getGrandTotal'])
            ->getMockForAbstractClass();
        $quote->expects($this->exactly($getQuoteCurrencyCodeInvokesCount))->method('getQuoteCurrencyCode')
            ->willReturn($quoteCurrencyCode);
        $quote->expects($this->never())->method('getGrandTotal');

        $this->assertEquals(1, $this->creditCheckoutData->getCurrencyConvertedRate($quote, $currencyCode));
    }

    /**
     * Test for isBaseCreditCurrencyRateEnabled().
     *
     * @return void
     */
    public function testIsBaseCreditCurrencyRateEnabled()
    {
        $baseCurrencyCode = 'USD';
        $toCurrencyCode = 'USD';
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);

        $this->assertTrue($this->creditCheckoutData->isBaseCreditCurrencyRateEnabled($quote, $toCurrencyCode));
    }

    /**
     * Test for isBaseCreditCurrencyRateEnabled() with different currencies.
     *
     * @return void
     */
    public function testIsBaseCreditCurrencyRateEnabledWithDifferentCurrencies()
    {
        $baseCurrencyCode = 'USD';
        $toCurrencyCode = 'EUR';
        $rate = 1.10;

        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $this->getBaseRatePrepareMocks($rate);

        $this->assertTrue($this->creditCheckoutData->isBaseCreditCurrencyRateEnabled($quote, $toCurrencyCode));
    }

    /**
     * Test for getBaseRate().
     *
     * @return void
     */
    public function testGetBaseRate()
    {
        $fromCurrencyCode = 'USD';
        $toCurrencyCode = 'EUR';
        $rate = 1.10;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($fromCurrencyCode);
        $this->getBaseRatePrepareMocks($rate);

        $this->assertEquals($rate, $this->creditCheckoutData->getBaseRate($quote, $toCurrencyCode));
    }

    /**
     * Test for getGrandTotalInCreditCurrency().
     *
     * @return void
     */
    public function testGetGrandTotalInCreditCurrency()
    {
        $baseCurrencyCode = 'USD';
        $toCurrencyCode = 'USD';
        $grandTotal = 100.00;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal', 'getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getBaseGrandTotal')->willReturn($grandTotal);
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);

        $this->assertEquals(
            $grandTotal,
            $this->creditCheckoutData->getGrandTotalInCreditCurrency($quote, $toCurrencyCode)
        );
    }

    /**
     * Test for getGrandTotalInCreditCurrency() with different currencies.
     *
     * @return void
     */
    public function testGetGrandTotalInCreditCurrencyWithDifferentCurrencies()
    {
        $baseCurrencyCode = 'USD';
        $toCurrencyCode = 'EUR';
        $baseGrandTotal = 100.00;
        $grandTotal = 95.00;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal', 'getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getBaseGrandTotal')->willReturn($baseGrandTotal);
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $toCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fromCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fromCurrency->expects($this->atLeastOnce())->method('convert')->willReturn($grandTotal);
        $this->websiteCurrency->expects($this->atLeastOnce())->method('getCurrencyByCode')
            ->willReturnOnConsecutiveCalls($fromCurrency, $toCurrency);

        $this->assertEquals(
            $grandTotal,
            $this->creditCheckoutData->getGrandTotalInCreditCurrency($quote, $toCurrencyCode)
        );
    }

    /**
     * Test for getGrandTotalInCreditCurrency() with Exception.
     *
     * @return void
     */
    public function testGetGrandTotalInCreditCurrencyWithException()
    {
        $baseCurrencyCode = 'USD';
        $toCurrencyCode = 'EUR';
        $baseGrandTotal = 100.00;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseGrandTotal', 'getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getBaseGrandTotal')->willReturn($baseGrandTotal);
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $toCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fromCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exception = new \Exception();
        $fromCurrency->expects($this->atLeastOnce())->method('convert')->willThrowException($exception);
        $this->websiteCurrency->expects($this->atLeastOnce())->method('getCurrencyByCode')
            ->willReturnOnConsecutiveCalls($fromCurrency, $toCurrency);

        $this->assertNull($this->creditCheckoutData->getGrandTotalInCreditCurrency($quote, $toCurrencyCode));
    }

    /**
     * Test for formatPrice().
     *
     * @return void
     */
    public function testFormatPrice()
    {
        $price = 100;
        $formattedPrice = '$100.00';
        $currency = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCurrency->expects($this->atLeastOnce())
            ->method('format')
            ->with($price, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currency)
            ->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->creditCheckoutData->formatPrice($price, $currency));
    }

    /**
     * Test for getPriceFormatPattern().
     *
     * @param string|null $currencySymbol
     * @param string|null $currencyCode
     * @param int $getCurrencyCodeInvokesCount
     * @param string $priceFormatPattern
     * @return void
     * @dataProvider getPriceFormatPatternDataProvider
     */
    public function testGetPriceFormatPattern(
        $currencySymbol,
        $currencyCode,
        $getCurrencyCodeInvokesCount,
        $priceFormatPattern
    ) {
        $currency = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrencySymbol', 'getCurrencyCode'])
            ->getMock();
        $this->websiteCurrency->expects($this->atLeastOnce())->method('getCurrencyByCode')->with($currencyCode)
            ->willReturn($currency);
        $currency->expects($this->atLeastOnce())->method('getCurrencySymbol')->willReturn($currencySymbol);
        $currency->expects($this->exactly($getCurrencyCodeInvokesCount))->method('getCurrencyCode')
            ->willReturn($currencyCode);

        $this->assertEquals($priceFormatPattern, $this->creditCheckoutData->getPriceFormatPattern($currencyCode));
    }

    /**
     * Test for getCompanyId().
     *
     * @return void
     */
    public function testGetCompanyId()
    {
        $userId = 1;
        $companyId = 2;
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->atLeastOnce())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->customerRepository->expects($this->atLeastOnce())->method('getById')->with($userId)
            ->willReturn($customer);

        $this->assertEquals($companyId, $this->creditCheckoutData->getCompanyId());
    }

    /**
     * Test for getCompanyId() for anonymous user.
     *
     * @return void
     */
    public function testGetCompanyIdForAnonymousUser()
    {
        $userId = 1;
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->atLeastOnce())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn(null);
        $this->customerRepository->expects($this->atLeastOnce())->method('getById')->with($userId)
            ->willReturn($customer);

        $this->assertNull($this->creditCheckoutData->getCompanyId());
    }

    /**
     * Test for getCompanyId() with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetCompanyIdWithException()
    {
        $userId = 1;
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($userId);
        $this->userContext->expects($this->atLeastOnce())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $exception = new NoSuchEntityException(__('Exception Message'));
        $this->customerRepository->expects($this->atLeastOnce())->method('getById')->willThrowException($exception);

        $this->assertNull($this->creditCheckoutData->getCompanyId());
    }

    /**
     * Prepare mocks for testGetBaseRate() method.
     *
     * @param float $rate
     * @return void
     */
    private function getBaseRatePrepareMocks($rate)
    {
        $toCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fromCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteCurrency->expects($this->atLeastOnce())->method('getCurrencyByCode')
            ->willReturnOnConsecutiveCalls($toCurrency, $fromCurrency);
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency->expects($this->atLeastOnce())->method('getRate')->with($toCurrency)->willReturn($rate);
        $this->priceCurrency->expects($this->atLeastOnce())->method('getCurrency')->willReturn($currency);
    }

    /**
     * DataProvider for testGetCurrencyConvertedRateWithTheSameCurrencies().
     *
     * @return array
     */
    public function getCurrencyConvertedRateWithTheSameCurrenciesDataProvider()
    {
        return [
            [null, null, 0],
            ['USD', 'USD', 1]
        ];
    }

    /**
     * Data provider for testGetPriceFormatPattern().
     *
     * @return array
     */
    public function getPriceFormatPatternDataProvider()
    {
        return [
            ['$', 'USD', 0, '$%s'],
            [null, 'CHF', 2, 'CHF%s'],
            [null, null, 1, '%s']
        ];
    }
}
