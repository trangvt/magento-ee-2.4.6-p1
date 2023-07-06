<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Model\CreditCheckoutData;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Exception;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyCreditPaymentConfigProviderTest extends TestCase
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
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * @var CreditCheckoutData|MockObject
     */
    private $creditCheckoutData;

    /**
     * @var CompanyCreditPaymentConfigProvider
     */
    private $configProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContext = $this->createMock(
            UserContextInterface::class
        );
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->creditDataProvider = $this->createMock(
            CreditDataProviderInterface::class
        );
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );
        $this->priceCurrency = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->context = $this->createMock(
            Context::class
        );
        $this->websiteCurrency = $this->createMock(
            WebsiteCurrency::class
        );
        $this->creditCheckoutData = $this->createMock(
            CreditCheckoutData::class
        );

        $objectManager = new ObjectManager($this);
        $this->configProvider = $objectManager->getObject(
            CompanyCreditPaymentConfigProvider::class,
            [
                'userContext' => $this->userContext,
                'customerRepository' => $this->customerRepository,
                'creditDataProvider' => $this->creditDataProvider,
                'quoteRepository' => $this->quoteRepository,
                'companyRepository' => $this->companyRepository,
                'priceCurrency' => $this->priceCurrency,
                'context' => $this->context,
                'websiteCurrency' => $this->websiteCurrency,
                'creditCheckoutData' => $this->creditCheckoutData
            ]
        );
    }

    /**
     * Test for method getConfig.
     *
     * @param ReturnStub|Exception $quoteResult
     * @param int $negotiableInvocationsCount
     * @param MockObject $quote
     * @return void
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(
        $quoteResult,
        $negotiableInvocationsCount,
        MockObject $quote
    ) {
        $userId = 1;
        $companyId = 2;
        $quoteId = 3;
        $availableLimit = 50;
        $exceedLimit = true;
        $quoteTotal = 750;
        $companyName = 'Company Name';
        $creditCurrency = 'USD';
        $isOrderPlaceEnabled = true;
        $currencyConvertedRate = 1;
        $this->creditCheckoutData->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->userContext->expects($this->any())->method('getUserId')->willReturn($userId);
        $creditData = $this->getMockForAbstractClass(CreditDataInterface::class);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($creditData);
        $creditData->expects($this->exactly(7))->method('getCurrencyCode')->willReturn($creditCurrency);
        $this->quoteRepository->expects($this->once())
            ->method('getActiveForCustomer')->with($userId)->will($quoteResult);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->context->expects($this->exactly($negotiableInvocationsCount))
            ->method('getRequest')->willReturn($request);
        $request->expects($this->exactly($negotiableInvocationsCount))
            ->method('getParam')->with('negotiableQuoteId')->willReturn($quoteId);
        $this->quoteRepository->expects($this->exactly($negotiableInvocationsCount))
            ->method('get')->with($quoteId)->willReturn($quote);
        $creditData->expects($this->exactly(3))->method('getAvailableLimit')->willReturn($availableLimit);
        $creditData->expects($this->once())->method('getExceedLimit')->willReturn($exceedLimit);
        $quote->expects($this->exactly(2))->method('getBaseGrandTotal')->willReturn($quoteTotal);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $creditCurrencyMock = $this->createMock(Currency::class);
        $this->websiteCurrency->expects($this->once())->method('getCurrencyByCode')
            ->with($creditCurrency)->willReturn($creditCurrencyMock);
        $this->creditCheckoutData->expects($this->once())->method('getGrandTotalInCreditCurrency')
            ->willReturn($quoteTotal);
        $this->creditCheckoutData->expects($this->once())->method('isBaseCreditCurrencyRateEnabled')
            ->willReturn($isOrderPlaceEnabled);
        $this->creditCheckoutData->expects($this->once())->method('getCurrencyConvertedRate')
            ->willReturn($currencyConvertedRate);
        $this->creditCheckoutData->expects($this->exactly(3))->method('formatPrice')
            ->withConsecutive(
                [$availableLimit, $creditCurrencyMock],
                [$quoteTotal, $creditCurrencyMock],
                [$quoteTotal - $availableLimit, $creditCurrencyMock]
            )->willReturnOnConsecutiveCalls(
                '$' . $availableLimit,
                '$' . $quoteTotal,
                '$' . ($quoteTotal - $availableLimit)
            );

        $expectedResult = [
            'payment' => [
                'companycredit' => [
                    'limit' => $availableLimit,
                    'exceedLimit' => $exceedLimit,
                    'limitFormatted' => '$' . $availableLimit,
                    'quoteTotalFormatted' => '$' . ($quoteTotal),
                    'exceededAmountFormatted' => '$' . ($quoteTotal - $availableLimit),
                    'currency' => $creditCurrency,
                    'rate' => $currencyConvertedRate,
                    'companyName' => $companyName,
                    'isBaseCreditCurrencyRateEnabled' => true,
                    'priceFormatPattern' => null,
                    'baseRate' => null
                ]
            ]
        ];
        $this->assertEquals($expectedResult, $this->configProvider->getConfig());
    }

    /**
     * Test for method getConfig with empty company ID.
     *
     * @return void
     */
    public function testGetConfigWithEmptyCompanyId()
    {
        $this->creditCheckoutData->expects($this->once())->method('getCompanyId')->willReturn(null);
        $this->assertEquals([], $this->configProvider->getConfig());
    }

    /**
     * Data provider for testGetConfig.
     *
     * @return array
     */
    public function getConfigDataProvider()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getQuoteCurrencyCode', 'getGrandTotal', 'getBaseCurrencyCode', 'getBaseGrandTotal'])
            ->disableOriginalConstructor()
            ->getMock();
        return [
            [
                $this->returnValue($quote),
                0,
                $quote,
            ],
            [
                $this->throwException(new NoSuchEntityException()),
                1,
                clone $quote,
            ]
        ];
    }
}
