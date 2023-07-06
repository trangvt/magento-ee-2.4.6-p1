<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History\Listing\Column;

use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\CompanyCredit\Ui\Component\History\Listing\Column\CurrencyCredit;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyCreditTest extends TestCase
{
    /**
     * @var CurrencyCredit
     */
    private $currencyCreditColumn;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceFormatter;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->priceFormatter = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->websiteCurrency = $this->createMock(
            WebsiteCurrency::class
        );
        $context = $this->createMock(
            ContextInterface::class
        );
        $processor = $this->createMock(
            Processor::class
        );
        $context->expects($this->never())
            ->method('getProcessor')
            ->willReturn($processor);

        $objectManager = new ObjectManager($this);
        $this->currencyCreditColumn = $objectManager->getObject(
            CurrencyCredit::class,
            [
                'context' => $context,
                'priceFormatter' => $this->priceFormatter,
                'websiteCurrency' => $this->websiteCurrency
            ]
        );
        $this->currencyCreditColumn->setData('name', 'balance');
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSource(): void
    {
        $currencyCode = 'EUR';
        $dataSource = [
            'data' => [
                'items' => [
                    ['balance' => 100, 'currency_credit' => $currencyCode],
                    ['balance' => 200]
                ]
            ]
        ];

        $expected = [
            'data' => [
                'items' => [
                    ['balance' => '€100', 'currency_credit' => $currencyCode],
                    ['balance' => '$200']
                ]
            ]
        ];

        $currency = $this->createMock(Currency::class);
        $baseCurrency = $this->createMock(Currency::class);
        $this->websiteCurrency->expects($this->exactly(2))
            ->method('getCurrencyByCode')
            ->withConsecutive([$currencyCode], [null])
            ->willReturnOnConsecutiveCalls($currency, $baseCurrency);
        $this->priceFormatter->expects($this->any())
            ->method('format')
            ->withConsecutive(
                [100, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currency],
                [200, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrency]
            )
            ->willReturnOnConsecutiveCalls('€100', '$200');

        $this->assertEquals($expected, $this->currencyCreditColumn->prepareDataSource($dataSource));
    }
}
