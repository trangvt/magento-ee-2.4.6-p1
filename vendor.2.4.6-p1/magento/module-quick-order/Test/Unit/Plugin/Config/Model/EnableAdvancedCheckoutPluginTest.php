<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Plugin\Config\Model;

use Magento\AdvancedCheckout\Model\Cart\Sku\Source\Settings;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\QuickOrder\Model\Config;
use Magento\QuickOrder\Plugin\Config\Model\EnableAdvancedCheckoutPlugin;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EnableAdvancedCheckoutPlugin plugin.
 */
class EnableAdvancedCheckoutPluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var EnableAdvancedCheckoutPlugin
     */
    private $enableAdvancedCheckoutPlugin;

    /**
     * @var Config|MockObject
     */
    private $quickOrderConfigMock;

    /**
     * @var ConfigInterface|MockObject
     */
    private $configResourceMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quickOrderConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configResourceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'saveConfig',
                'deleteConfig'
            ])
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStore',
                'getWebsite'
            ])
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->enableAdvancedCheckoutPlugin = $this->objectManagerHelper->getObject(
            EnableAdvancedCheckoutPlugin::class,
            [
                'quickOrderConfig' => $this->quickOrderConfigMock,
                'configResource' => $this->configResourceMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    /**
     * Test for aroundSave() method when store scope applied for configuration.
     *
     * @return void
     */
    public function testAroundSaveForStoreScope()
    {
        $storeCode = 'store_code';
        $storeId = 1;

        $configMock = $this->getMockBuilder(\Magento\Config\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStore',
                'getWebsite'
            ])
            ->getMock();
        $configMock->expects($this->atLeastOnce())->method('getStore')->willReturn($storeCode);
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCode',
                'getId'
            ])
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getStore')->with($storeCode)
            ->willReturn($storeMock);
        $this->storeManagerMock->expects($this->never())->method('getWebsite');
        $storeMock->expects($this->atLeastOnce())->method('getCode')->willReturn($storeCode);
        $storeMock->expects($this->atLeastOnce())->method('getId')->willReturn($storeId);
        $this->quickOrderConfigMock->expects($this->exactly(2))->method('isActive')->with('stores', $storeCode)
            ->willReturnOnConsecutiveCalls(false, true);
        $closure = function () use ($configMock) {
            return $configMock;
        };
        $this->configResourceMock->expects($this->once())->method('saveConfig')->with(
            'sales/product_sku/my_account_enable',
            Settings::YES_VALUE,
            'stores',
            $storeId
        );

        $this->assertSame($configMock, $this->enableAdvancedCheckoutPlugin->aroundSave($configMock, $closure));
    }

    /**
     * Test for aroundSave() method when website scope applied for configuration.
     *
     * @return void
     */
    public function testAroundSaveForWebsiteScope()
    {
        $websiteCode = 'website_code';
        $websiteId = 1;

        $configMock = $this->getMockBuilder(\Magento\Config\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStore',
                'getWebsite'
            ])
            ->getMock();
        $configMock->expects($this->atLeastOnce())->method('getStore')->willReturn(false);
        $configMock->expects($this->atLeastOnce())->method('getWebsite')->willReturn($websiteCode);
        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCode',
                'getId'
            ])
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->never())->method('getStore');
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getWebsite')->with($websiteCode)
            ->willReturn($websiteMock);
        $websiteMock->expects($this->atLeastOnce())->method('getCode')->willReturn($websiteCode);
        $websiteMock->expects($this->atLeastOnce())->method('getId')->willReturn($websiteId);
        $this->quickOrderConfigMock->expects($this->exactly(2))->method('isActive')->with('websites', $websiteCode)
            ->willReturnOnConsecutiveCalls(false, true);
        $closure = function () use ($configMock) {
            return $configMock;
        };
        $this->configResourceMock->expects($this->once())->method('saveConfig')->with(
            'sales/product_sku/my_account_enable',
            Settings::YES_VALUE,
            'websites',
            $websiteId
        );

        $this->assertSame($configMock, $this->enableAdvancedCheckoutPlugin->aroundSave($configMock, $closure));
    }

    /**
     * @return void
     */
    public function testAroundSave()
    {
        $scopeCode = null;
        $scopeId = 0;
        $websiteId = 1;

        $configMock = $this->getMockBuilder(\Magento\Config\Model\Config::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStore',
                'getWebsite'
            ])
            ->getMock();
        $configMock->expects($this->atLeastOnce())->method('getStore')->willReturn(false);
        $this->storeManagerMock->expects($this->never())->method('getStore');
        $this->storeManagerMock->expects($this->never())->method('getWebsite');
        $this->quickOrderConfigMock->expects($this->exactly(2))->method('isActive')->with(
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeCode
        )
            ->willReturnOnConsecutiveCalls(false, true);
        $closure = function () use ($configMock) {
            return $configMock;
        };
        $this->configResourceMock->expects($this->once())->method('saveConfig')->with(
            'sales/product_sku/my_account_enable',
            Settings::YES_VALUE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId
        );

        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->once())->method('getWebsites')->willReturn([$websiteMock]);
        $websiteMock->expects($this->atLeastOnce())->method('getId')->willReturn($websiteId);
        $this->configResourceMock->expects($this->once())->method('deleteConfig')->with(
            'sales/product_sku/my_account_enable',
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        $this->assertSame($configMock, $this->enableAdvancedCheckoutPlugin->aroundSave($configMock, $closure));
    }
}
