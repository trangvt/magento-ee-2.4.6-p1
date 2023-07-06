<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Configure;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Currency;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use Magento\SharedCatalog\Ui\DataProvider\Configure\StepDataProcessor;
use Magento\SharedCatalog\Ui\DataProvider\Website;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\SharedCatalog\Ui\DataProvider\Configure\StepDataProcessor class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StepDataProcessorTest extends TestCase
{
    /**
     * @var Website|MockObject
     */
    private $websitesDataProvider;

    /**
     * @var PoolInterface|MockObject
     */
    private $modifiers;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var ProductItemTierPriceValidator|MockObject
     */
    private $productItemTierPriceValidator;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepositoryMock;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var StepDataProcessor
     */
    private $dataProvider;

    /**
     * Set up for test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->websitesDataProvider = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->modifiers = $this->getMockBuilder(PoolInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'setCurrentStore', 'getWebsite'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productItemTierPriceValidator = $this->getMockBuilder(
            ProductItemTierPriceValidator::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepositoryMock = $this->getMockBuilder(
            SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            StepDataProcessor::class,
            [
                'request' => $this->request,
                'modifiers' => $this->modifiers,
                'storeManager' => $this->storeManager,
                'websitesDataProvider' => $this->websitesDataProvider,
                'scopeConfig' => $this->scopeConfig,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'sharedCatalogRepository' => $this->sharedCatalogRepositoryMock,
                'localeCurrency' => $this->localeCurrency,
            ]
        );
    }

    /**
     * Test for modifyData method.
     *
     * @return void
     */
    public function testModifyData()
    {
        $data = ['test_data'];
        $expectedResult = ['test_data_modified'];
        $modifier = $this->getMockBuilder(ModifierInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->modifiers->expects($this->once())->method('getModifiersInstances')->willReturn([$modifier]);
        $modifier->expects($this->once())->method('modifyData')->with($data)->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->dataProvider->modifyData($data));
    }

    /**
     * Test for switchCurrentStore method.
     *
     * @return void
     */
    public function testSwitchCurrentStore()
    {
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->atLeastOnce())->method('getCode')->willReturn('test_code');
        $this->request->expects($this->once())->method('getParam')->with('filters')->willReturn(['websites' => 1]);
        $this->websitesDataProvider->expects($this->once())->method('getStoreByWebsiteId')->with(1)->willReturn($store);
        $this->storeManager->expects($this->once())->method('setCurrentStore')->with('test_code');
        $this->dataProvider->switchCurrentStore();
    }

    /**
     * Test for isCustomPriceEnabled method.
     *
     * @return void
     */
    public function testGetWebsites()
    {
        $websites = [
            [
                'value' => 0,
                'label' => __('All Websites')
            ],
            [
                'value' => 3,
                'label' => __('Website 3')
            ]
        ];
        $result = [
            'items' => $websites,
            'selected' => 3,
            'isPriceScopeGlobal' => true,
            'currencySymbol' => '$'
        ];
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode'])
            ->getMockForAbstractClass();
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websitesDataProvider->expects($this->once())->method('getWebsites')->willReturn($websites);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Store::XML_PATH_PRICE_SCOPE,
                ScopeInterface::SCOPE_STORE
            )->willReturn(Store::PRICE_SCOPE_GLOBAL);
        $this->request->expects($this->once())->method('getParam')->with('filters')->willReturn(['websites' => 3]);
        $this->storeManager->expects($this->once())->method('getWebsite')->with(3)->willReturn($website);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->localeCurrency->expects($this->once())->method('getCurrency')->with('USD')->willReturn($currency);
        $currency->expects($this->once())->method('getSymbol')->willReturn('$');

        $this->assertEquals($result, $this->dataProvider->getWebsites());
    }

    /**
     * Test for isCustomPriceEnabled method.
     *
     * @param bool $result
     * @param array $customPrices
     * @return void
     * @dataProvider isCustomPriceEnabledDataProvider
     */
    public function testIsCustomPriceEnabled($result, array $customPrices)
    {
        $websiteId = 2;
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('filters')
            ->willReturn(['websites' => $websiteId]);
        $this->productItemTierPriceValidator->expects($this->once())
            ->method('canChangePrice')
            ->with($customPrices, $websiteId)
            ->willReturn($result);
        $this->assertEquals($result, $this->dataProvider->isCustomPriceEnabled($customPrices));
    }

    /**
     * Test for retrieveSharedCatalogWebsiteIds() method.
     *
     * @return void
     */
    public function testRetrieveSharedCatalogWebsiteIds()
    {
        $sharedCatalogId = 1;
        $expectedWebsiteId = 1;

        $sharedCatalogMock = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogMock->expects($this->once())->method('getStoreId')->willReturn(null);
        $this->storeManager->expects($this->once())->method('getWebsites')->willReturn([1 => 'test']);
        $this->sharedCatalogRepositoryMock->expects($this->once())->method('get')->with($sharedCatalogId)
            ->willReturn($sharedCatalogMock);

        $this->assertEquals(
            [$expectedWebsiteId],
            $this->dataProvider->retrieveSharedCatalogWebsiteIds($sharedCatalogId)
        );
    }

    /**
     * Test for retrieveSharedCatalogWebsiteIds() method when shared catalog has store ID.
     *
     * @return void
     */
    public function testRetrieveSharedCatalogWebsiteIdsHasStoreId()
    {
        $sharedCatalogId = 1;
        $expectedWebsiteId = 1;
        $defaultStoreId = 1;

        $sharedCatalogMock = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogMock->expects($this->once())->method('getStoreId')->willReturn($defaultStoreId);
        $storeMock = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getGroup')->with($defaultStoreId)
            ->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($expectedWebsiteId);
        $this->sharedCatalogRepositoryMock->expects($this->once())->method('get')->with($sharedCatalogId)
            ->willReturn($sharedCatalogMock);

        $this->assertEquals(
            [$expectedWebsiteId],
            $this->dataProvider->retrieveSharedCatalogWebsiteIds($sharedCatalogId)
        );
    }

    /**
     * Test prepareCustomPrice method.
     *
     * @return void
     */
    public function testPrepareCustomPrice()
    {
        $websiteId = 0;
        $customPricesData = [
            'custom_prices_data_0',
            'custom_prices_data_1'
        ];
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('filters')
            ->willReturn(['websites' => $websiteId]);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                Store::XML_PATH_PRICE_SCOPE,
                ScopeInterface::SCOPE_STORE
            )->willReturn(Store::PRICE_SCOPE_WEBSITE);
        $this->assertNull($this->dataProvider->prepareCustomPrice($customPricesData));
    }

    /**
     * Data provider for getData method.
     *
     * @return array
     */
    public function isCustomPriceEnabledDataProvider()
    {
        return [
            [
                false,
                [1, 2]
            ],
            [
                true,
                [1]
            ],
        ];
    }
}
