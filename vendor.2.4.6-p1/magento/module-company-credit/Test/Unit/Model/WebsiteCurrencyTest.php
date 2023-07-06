<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteCurrencyTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CurrencyFactory|MockObject
     */
    private $currencyFactory;

    /**
     * @var WebsiteCurrency
     */
    private $websiteCurrency;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(
            StoreManagerInterface::class
        );
        $website = $this->createMock(
            Website::class
        );
        $website->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->method('getWebsites')->willReturn([$website]);
        $baseWebsite = $this->createMock(
            Website::class
        );
        $baseWebsite->method('getBaseCurrencyCode')->willReturn('USD_BASE');
        $this->storeManager->method('getWebsite')->willReturn($baseWebsite);
        $this->currencyFactory = $this->createPartialMock(
            CurrencyFactory::class,
            ['create']
        );
        $objectManager = new ObjectManager($this);
        $this->websiteCurrency = $objectManager->getObject(
            WebsiteCurrency::class,
            [
                'storeManager' => $this->storeManager,
                'currencyFactory' => $this->currencyFactory,
            ]
        );
    }

    /**
     * Test isCreditCurrencyEnabled method.
     */
    public function testIsCreditCurrencyEnabled()
    {
        $this->assertTrue($this->websiteCurrency->isCreditCurrencyEnabled('USD'));
    }

    /**
     * Test getAllowedCreditCurrencies method.
     */
    public function testGetAllowedCreditCurrencies()
    {
        $this->assertNotEmpty($this->websiteCurrency->getAllowedCreditCurrencies());
        $this->assertCount(2, $this->websiteCurrency->getAllowedCreditCurrencies());
    }

    /**
     * Test getCurrencyByCode method.
     */
    public function testGetCurrencyByCode()
    {
        $code = 'Euro';
        $currency = $this->createMock(
            Currency::class
        );
        $currency->method('load')->willReturn('codeInstance');
        $this->currencyFactory->method('create')->willReturn($currency);
        $this->assertNotEmpty($this->websiteCurrency->getCurrencyByCode($code));
        $this->assertEquals('codeInstance', $this->websiteCurrency->getCurrencyByCode($code));
    }

    /**
     * Test getCurrencyByCode method.
     */
    public function testGetCurrencyByCodeWithEmptyParameter()
    {
        $code = 'Euro';
        $store = $this->createMock(
            Store::class
        );
        $store->method('getBaseCurrency')->willReturn($code);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->assertEquals(
            $code,
            $this->websiteCurrency->getCurrencyByCode(false)
        );
    }
}
