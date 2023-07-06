<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Controller\Adminhtml\Currency;

use Exception;
use Magento\CompanyCredit\Controller\Adminhtml\Currency\GetRate;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetRateTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var GetRate
     */
    private $getRate;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->priceCurrency = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->getRate = $objectManager->getObject(
            GetRate::class,
            [
                'priceCurrency' => $this->priceCurrency,
                'logger' => $this->logger,
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $creditCurrency = 'EUR';
        $newCurrency = 'USD';
        $currencySymbol = '$';
        $rate = 1.4;
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['currency_from'], ['currency_to'])
            ->willReturnOnConsecutiveCalls($creditCurrency, $newCurrency);
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);

        $currency = $this->createMock(Currency::class);
        $targetCurrency = $this->createMock(Currency::class);
        $this->priceCurrency->expects($this->exactly(2))
            ->method('getCurrency')
            ->withConsecutive([], [null, $newCurrency])
            ->willReturnOnConsecutiveCalls($currency, $targetCurrency);
        $currency->expects($this->once())
            ->method('getCurrencyRates')
            ->with($creditCurrency, [$newCurrency])
            ->willReturn([$newCurrency => $rate]);
        $targetCurrency->expects($this->once())
            ->method('getCurrencySymbol')
            ->willReturn($currencySymbol);
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'success',
                    'currency_rate' => '1.4000',
                    'currency_symbol' => $currencySymbol
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($result, $this->getRate->execute());
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $exception = new Exception();
        $creditCurrency = 'EUR';
        $newCurrency = 'USD';
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['currency_from'], ['currency_to'])
            ->willReturnOnConsecutiveCalls($creditCurrency, $newCurrency);
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')
            ->willThrowException($exception);
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'error' => __('Something went wrong. Please try again later.')
                ]
            )
            ->willReturnSelf();
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->assertEquals($result, $this->getRate->execute());
    }

    /**
     * Test for method execute with localized exception.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException(): void
    {
        $exceptionMessage = 'Exception Message';
        $creditCurrency = 'EUR';
        $newCurrency = 'USD';
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['currency_from'], ['currency_to'])
            ->willReturnOnConsecutiveCalls($creditCurrency, $newCurrency);
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')
            ->willThrowException(new LocalizedException(__($exceptionMessage)));
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'error' => $exceptionMessage
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($result, $this->getRate->execute());
    }
}
