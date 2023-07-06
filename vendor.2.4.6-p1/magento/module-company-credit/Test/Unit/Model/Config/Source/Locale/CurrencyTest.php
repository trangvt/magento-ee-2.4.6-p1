<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Config\Source\Locale;

use Magento\CompanyCredit\Model\Config\Source\Locale\Currency;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Framework\Locale\Bundle\CurrencyBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * @var CurrencyBundle|MockObject
     */
    private $currencyBundle;

    /**
     * @var ResolverInterface|MockObject
     */
    private $localeResolver;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->websiteCurrency = $this->createMock(
            WebsiteCurrency::class
        );
        $this->currencyBundle = $this->createMock(
            CurrencyBundle::class
        );
        $this->localeResolver = $this->createMock(
            ResolverInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->currency = $objectManager->getObject(
            Currency::class,
            [
                'websiteCurrency' => $this->websiteCurrency,
                'currencyBundle' => $this->currencyBundle,
                'localeResolver' => $this->localeResolver,
            ]
        );
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $locale = 'en_US';
        $this->localeResolver->expects($this->once())->method('getLocale')->willReturn($locale);
        $this->currencyBundle->expects($this->once())->method('get')->with($locale)
            ->willReturn([
                'Currencies' => [
                    'EUR' => ['€', 'Euro'],
                    'USD' => ['$', 'US Dollar'],
                ],
            ]);
        $this->websiteCurrency->expects($this->once())
            ->method('getAllowedCreditCurrencies')->willReturn(['USD' => 'USD']);
        $this->assertEquals([['label' => 'US Dollar', 'value' => 'USD']], $this->currency->toOptionArray());
    }
}
