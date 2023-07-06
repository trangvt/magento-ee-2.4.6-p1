<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Config\Source\ProductPriceOptionsInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Currency;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Ui\DataProvider\Modifier\TierPrice;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\SharedCatalog\Ui\DataProvider\Modifier\TierPrice.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TierPriceTest extends TestCase
{
    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ProductPriceOptionsInterface|MockObject
     */
    private $productPriceOptions;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var TierPrice
     */
    private $tierPrice;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->localeCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productPriceOptions = $this
            ->getMockBuilder(ProductPriceOptionsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository = $this
            ->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->tierPrice = $objectManager->getObject(
            TierPrice::class,
            [
                'localeCurrency' => $this->localeCurrency,
                'storeManager' => $this->storeManager,
                'productPriceOptions' => $this->productPriceOptions,
                'request' => $this->request,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'productRepository' => $this->productRepository,
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * Test for modifyMeta method.
     *
     * @return void
     */
    public function testModifyMeta()
    {
        $meta = ['meta_key1' => 'meta_value1'];
        $sharedCatalogId = 1;
        $storeId = 0;
        $websiteId = 2;
        $productId = 3;
        $baseCurrencyCode = 'USD';
        $currencySymbol = '$';
        $websiteName = 'Website 1';
        $websiteOptions = [
            [
                'label' => __('All Websites') . ' [' . $baseCurrencyCode . ']',
                'value' => 0,
            ],
            [
                'label' => $websiteName . '[' . $baseCurrencyCode . ']',
                'value' => $websiteId,
            ]
        ];
        $priceOptions = ['option_key1' => 'option_value1'];
        $this->storeManager->expects($this->atLeastOnce())->method('isSingleStoreMode')->willReturn(false);
        $this->scopeConfig->expects($this->exactly(2))->method('getValue')
            ->withConsecutive(
                ['currency/options/base', 'default'],
                ['catalog/price/scope', 'store']
            )->willReturnOnConsecutiveCalls($baseCurrencyCode, Store::PRICE_SCOPE_WEBSITE);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(
                [SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM],
                ['store_id'],
                ['product_id']
            )->willReturnOnConsecutiveCalls($sharedCatalogId, $storeId, $productId);
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('getStoreId')->willReturn(null);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')->with($sharedCatalogId)->willReturn($sharedCatalog);
        $store = $this->getMockBuilder(GroupInterface::class)
            ->setMethods(['getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getGroup')->with($storeId)->willReturn($store);
        $store->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $currency = $this->getMockBuilder(Currency::class)
            ->setMethods(['getCurrencySymbol'])
            ->disableOriginalConstructor()
            ->getMock();
        $website = $this->getMockBuilder(Website::class)
            ->setMethods(['getBaseCurrencyCode', 'getBaseCurrency', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getWebsites')->willReturn([$website]);
        $this->storeManager->expects($this->once())->method('getWebsite')->with($websiteId)->willReturn($website);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productExtensionAttributes = $this->getMockBuilder(ProductExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteIds'])
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())->method('getById')->willReturn($product);
        $product->expects($this->once())->method('getExtensionAttributes')->willReturn($productExtensionAttributes);
        $productExtensionAttributes->expects($this->once())->method('getWebsiteIds')->willReturn([$websiteId]);
        $website->expects($this->once())->method('getName')->willReturn($websiteName);
        $website->expects($this->atLeastOnce())->method('getId')->willReturn($websiteId);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $website->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $currency->expects($this->once())->method('getCurrencySymbol')->willReturn($currencySymbol);
        $this->productPriceOptions->expects($this->once())->method('toOptionArray')->willReturn($priceOptions);
        $tierPriceStructure = $this->getTierPriceStructure($websiteOptions, $websiteId, $currencySymbol, $priceOptions);
        $this->assertEquals(
            array_merge($meta, ['tier_price_fs' => ['children' => ['tier_price' => $tierPriceStructure]]]),
            $this->tierPrice->modifyMeta($meta)
        );
    }

    /**
     * Test for modifyMeta method with not applicable website.
     *
     * @return void
     */
    public function testModifyMetaWithNotApplicableWebsite()
    {
        $meta = ['meta_key1' => 'meta_value1'];
        $sharedCatalogId = 1;
        $storeId = 0;
        $websiteId = 2;
        $productId = 3;
        $baseCurrencyCode = 'USD';
        $currencySymbol = '$';
        $websiteOptions = [
            [
                'label' => __('All Websites') . ' [' . $baseCurrencyCode . ']',
                'value' => 0,
            ]
        ];
        $priceOptions = ['option_key1' => 'option_value1'];
        $this->storeManager->expects($this->atLeastOnce())->method('isSingleStoreMode')->willReturn(false);
        $this->scopeConfig->expects($this->exactly(2))->method('getValue')
            ->withConsecutive(
                ['currency/options/base', 'default'],
                ['catalog/price/scope', 'store']
            )->willReturnOnConsecutiveCalls($baseCurrencyCode, Store::PRICE_SCOPE_WEBSITE);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(
                [SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM],
                ['store_id'],
                ['product_id']
            )->willReturnOnConsecutiveCalls($sharedCatalogId, $storeId, $productId);
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('getStoreId')->willReturn(null);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')->with($sharedCatalogId)->willReturn($sharedCatalog);
        $store = $this->getMockBuilder(GroupInterface::class)
            ->setMethods(['getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getGroup')->with($storeId)->willReturn($store);
        $store->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $currency = $this->getMockBuilder(Currency::class)
            ->setMethods(['getCurrencySymbol'])
            ->disableOriginalConstructor()
            ->getMock();
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getBaseCurrencyCode', 'getBaseCurrency'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $this->storeManager->expects($this->once())->method('getWebsite')->with($websiteId)->willReturn($website);
        $this->storeManager->expects($this->once())->method('getWebsites')->willReturn([$website]);
        $this->productRepository->expects($this->once())->method('getById')
            ->willThrowException(new NoSuchEntityException());
        $currency->expects($this->once())->method('getCurrencySymbol')->willReturn($currencySymbol);
        $this->productPriceOptions->expects($this->once())->method('toOptionArray')->willReturn($priceOptions);
        $tierPriceStructure = $this->getTierPriceStructure($websiteOptions, $websiteId, $currencySymbol, $priceOptions);
        $this->assertEquals(
            array_merge($meta, ['tier_price_fs' => ['children' => ['tier_price' => $tierPriceStructure]]]),
            $this->tierPrice->modifyMeta($meta)
        );
    }

    /**
     * Test for modifyMeta method with selected website.
     *
     * @return void
     */
    public function testModifyMetaWithSelectedWebsite()
    {
        $meta = ['meta_key1' => 'meta_value1'];
        $sharedCatalogId = 1;
        $storeId = 2;
        $websiteId = 3;
        $baseCurrencyCode = 'USD';
        $currencySymbol = '$';
        $websiteName = 'Website 1';
        $websiteOptions = [
            [
                'label' => $websiteName . '[' . $baseCurrencyCode . ']',
                'value' => $websiteId,
            ]
        ];
        $priceOptions = ['option_key1' => 'option_value1'];
        $this->storeManager->expects($this->exactly(2))->method('isSingleStoreMode')->willReturn(false);
        $this->scopeConfig->expects($this->once())->method('getValue')
            ->with('catalog/price/scope', 'store')->willReturn(Store::PRICE_SCOPE_WEBSITE);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(
                [SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM],
                ['store_id'],
                ['store_id'],
                ['store_id'],
                ['store_id']
            )->willReturnOnConsecutiveCalls($sharedCatalogId, $storeId, $storeId, $storeId, $storeId);
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('getStoreId')->willReturn(null);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')->with($sharedCatalogId)->willReturn($sharedCatalog);
        $store = $this->getMockBuilder(GroupInterface::class)
            ->setMethods(['getBaseCurrency'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getGroup')->with($storeId)->willReturn($store);
        $store->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $currency = $this->getMockBuilder(Currency::class)
            ->setMethods(['getCurrencySymbol'])
            ->disableOriginalConstructor()
            ->getMock();
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getBaseCurrencyCode', 'getBaseCurrency'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->exactly(2))->method('getWebsite')->with($websiteId)->willReturn($website);
        $website->expects($this->once())->method('getName')->willReturn($websiteName);
        $website->expects($this->once())->method('getId')->willReturn($websiteId);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $website->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $currency->expects($this->once())->method('getCurrencySymbol')->willReturn($currencySymbol);
        $this->productPriceOptions->expects($this->once())->method('toOptionArray')->willReturn($priceOptions);
        $tierPriceStructure = $this->getTierPriceStructure($websiteOptions, $websiteId, $currencySymbol, $priceOptions);
        $this->assertEquals(
            array_merge($meta, ['tier_price_fs' => ['children' => ['tier_price' => $tierPriceStructure]]]),
            $this->tierPrice->modifyMeta($meta)
        );
    }

    /**
     * Test for modifyMeta method in single store mode.
     *
     * @return void
     */
    public function testModifyMetaInSingleStoreMode()
    {
        $meta = ['meta_key1' => 'meta_value1'];
        $sharedCatalogId = 1;
        $storeId = 2;
        $baseCurrencyCode = 'USD';
        $currencySymbol = '$';
        $websiteOptions = [
            [
                'label' => __('All Websites') . ' [' . $baseCurrencyCode . ']',
                'value' => 0,
            ]
        ];
        $websiteId = 3;
        $priceOptions = ['option_key1' => 'option_value1'];
        $this->storeManager->expects($this->once())->method('isSingleStoreMode')->willReturn(true);
        $this->scopeConfig->expects($this->exactly(2))->method('getValue')
            ->withConsecutive(
                ['currency/options/base', 'default'],
                ['catalog/price/scope', 'store']
            )->willReturnOnConsecutiveCalls($baseCurrencyCode, Store::PRICE_SCOPE_GLOBAL);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->withConsecutive(
                [SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM],
                ['store_id'],
                ['store_id']
            )->willReturnOnConsecutiveCalls($sharedCatalogId, $storeId, $storeId);
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())->method('getStoreId')->willReturn(null);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')->with($sharedCatalogId)->willReturn($sharedCatalog);
        $store = $this->getMockBuilder(GroupInterface::class)
            ->setMethods(['getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getGroup')->with($storeId)->willReturn($store);
        $store->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $currency = $this->getMockBuilder(Currency::class)
            ->setMethods(['getCurrencySymbol'])
            ->disableOriginalConstructor()
            ->getMock();
        $currency->expects($this->once())->method('getCurrencySymbol')->willReturn($currencySymbol);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getBaseCurrencyCode', 'getBaseCurrency'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $website->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $this->storeManager->expects($this->once())->method('getWebsite')->with($websiteId)->willReturn($website);
        $this->productPriceOptions->expects($this->once())->method('toOptionArray')->willReturn($priceOptions);
        $tierPriceStructure = $this->getTierPriceStructure($websiteOptions, 0, $currencySymbol, $priceOptions);
        $this->assertEquals(
            array_merge($meta, ['tier_price_fs' => ['children' => ['tier_price' => $tierPriceStructure]]]),
            $this->tierPrice->modifyMeta($meta)
        );
    }

    /**
     * Get tier price dynamic rows structure.
     *
     * @param array $websiteOptions
     * @param int $defaultWebsiteId
     * @param string $currencySymbol
     * @param array $priceOptions
     * @return array
     */
    private function getTierPriceStructure(
        array $websiteOptions,
        $defaultWebsiteId,
        $currencySymbol,
        array $priceOptions
    ) {
        return [
            'children' => [
                'record' => [
                    'children' => [
                        'website_id' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'options' => $websiteOptions,
                                        'value' => $defaultWebsiteId,
                                        'visible' => true,
                                        'disabled' => false,
                                    ],
                                ],
                            ],
                        ],
                        'price_value' => [
                            'children' => [
                                'price' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'addbefore' => $currencySymbol,
                                            ]
                                        ]
                                    ],
                                ],
                                'value_type' => [
                                    'arguments' => [
                                        'data' => [
                                            'options' => $priceOptions,
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for modifyData method.
     *
     * @return void
     */
    public function testModifyData()
    {
        $data = ['data_key1' => 'data_value1'];
        $productId = 1;
        $websiteId = 2;
        $baseCurrencyCode = 'USD';
        $websiteCurrencyCode = 'EUR';
        $this->request->expects($this->once())->method('getParam')->with('product_id')->willReturn($productId);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')->with('currency/options/base', 'default')->willReturn($baseCurrencyCode);
        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getWebsites')->willReturn([$website]);
        $website->expects($this->once())->method('getId')->willReturn($websiteId);
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn($websiteCurrencyCode);
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeCurrency->expects($this->exactly(2))->method('getCurrency')
            ->withConsecutive([$baseCurrencyCode], [$websiteCurrencyCode])->willReturn($currency);
        $currency->expects($this->exactly(2))->method('getSymbol')->willReturnOnConsecutiveCalls('$', '€');
        $this->assertEquals(
            $data +
            [
                $productId => [
                    'base_currencies' => [
                        ['website_id' => 0, 'symbol' => '$'],
                        ['website_id' => $websiteId, 'symbol' => '€'],
                    ]
                ]
            ],
            $this->tierPrice->modifyData($data)
        );
    }
}
