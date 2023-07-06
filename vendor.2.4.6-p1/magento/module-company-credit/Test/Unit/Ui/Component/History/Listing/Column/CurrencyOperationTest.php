<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\History\Listing\Column;

use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\CompanyCredit\Ui\Component\History\Listing\Column\CurrencyOperation;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyOperationTest extends TestCase
{
    /**
     * @var CurrencyOperation
     */
    private $currencyOperationColumn;

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
        $this->currencyOperationColumn = $objectManager->getObject(
            CurrencyOperation::class,
            [
                'context' => $context,
                'priceFormatter' => $this->priceFormatter,
                'websiteCurrency' => $this->websiteCurrency
            ]
        );
        $this->currencyOperationColumn->setData('name', 'balance');
    }

    /**
     * Test method for prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSource(): void
    {
        $creditCurrencyCode = 'USD';
        $operationCurrencyCode = 'EUR';
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'balance' => 100,
                        'currency_operation' => $operationCurrencyCode,
                        'currency_credit' => $creditCurrencyCode,
                        'rate' => 1.2,
                        'rate_credit' => 1.5,
                        'type' => 3
                    ],
                    ['balance' => 200, 'type' => 3],
                    ['balance' => 300, 'type' => 1],
                    ['balance' => 400, 'type' => 2]
                ]
            ]
        ];

        $expected = [
            'data' => [
                'items' => [
                    [
                        'balance' => '€150 ($120)<br>USD/EUR: 0.8000',
                        'currency_operation' => $operationCurrencyCode,
                        'currency_credit' => $creditCurrencyCode,
                        'rate' => 1.2,
                        'rate_credit' => 1.5,
                        'type' => 3,
                        'balance_original' => 100
                    ],
                    ['balance' => '$200', 'type' => 3, 'balance_original' => 200],
                    ['balance' => '', 'type' => 1, 'balance_original' => 300],
                    ['balance' => '', 'type' => 2, 'balance_original' => 400]
                ]
            ]
        ];

        $currency = $this->createMock(Currency::class);
        $operationCurrency = $this->createMock(Currency::class);
        $this->websiteCurrency->expects($this->exactly(3))
            ->method('getCurrencyByCode')
            ->withConsecutive([$creditCurrencyCode], [$operationCurrencyCode], [null])
            ->willReturnOnConsecutiveCalls($currency, $operationCurrency, $currency);
        $this->priceFormatter->expects($this->any())
            ->method('format')
            ->withConsecutive(
                [150, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currency],
                [120, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $operationCurrency],
                [200, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currency]
            )
            ->willReturnOnConsecutiveCalls('€150', '$120', '$200');

        $this->assertEquals($expected, $this->currencyOperationColumn->prepareDataSource($dataSource));
    }
}
