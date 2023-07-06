<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Currency;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Price\ProductTierPriceLoader;
use Magento\SharedCatalog\Model\Price\TierPriceFetcher;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model ProductTierPriceLoader.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTierPriceLoaderTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var ProductItemTierPriceValidator|MockObject
     */
    private $productItemTierPriceValidator;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var TierPriceFetcher|MockObject
     */
    private $tierPriceFetcher;

    /**
     * @var ProductTierPriceLoader
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->localeCurrency = $this->getMockForAbstractClass(CurrencyInterface::class);
        $this->productItemTierPriceValidator = $this->createMock(ProductItemTierPriceValidator::class);
        $this->sharedCatalogRepository = $this->getMockForAbstractClass(SharedCatalogRepositoryInterface::class);
        $this->tierPriceFetcher = $this->createMock(TierPriceFetcher::class);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            ProductTierPriceLoader::class,
            [
                'storeManager' => $this->storeManager,
                'localeCurrency' => $this->localeCurrency,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'tierPriceFetcher' => $this->tierPriceFetcher,
            ]
        );
    }

    /**
     * Test populateProductTierPrices method.
     *
     * @param string $priceType
     * @param array $price
     * @return void
     * @dataProvider populateProductTierPricesDataProvider
     */
    public function testPopulateProductTierPrices($priceType, array $price)
    {
        $skus = ['test_sku1', 'test_sku2', 'test_sku3'];
        $websiteId = 3;
        $sharedCatalogId = 1;

        $sharedCatalog = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $product = $this->getMockForAbstractClass(ProductInterface::class);
        $tierPrice = $this->getMockForAbstractClass(TierPriceInterface::class);
        $storage = $this->createMock(Wizard::class);
        $product->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn(Type::TYPE_SIMPLE);
        $this->productItemTierPriceValidator->expects($this->atLeastOnce())
            ->method('isTierPriceApplicable')
            ->willReturn(true);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')
            ->with($sharedCatalogId)
            ->willReturn($sharedCatalog);
        $product->expects($this->exactly(count($skus)))
            ->method('getSku')
            ->willReturnOnConsecutiveCalls(...$skus);
        $tierPrice->expects($this->exactly(count($skus)))
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $tierPrice->expects($this->exactly(count($skus)))
            ->method('getQuantity')
            ->willReturn(1);
        $tierPrice->expects($this->exactly(count($skus)))
            ->method('getPriceType')
            ->willReturn($priceType);
        $tierPrice->expects($this->exactly(count($skus)))
            ->method('getPrice')
            ->willReturn(12);
        if ($priceType == 'fixed') {
            $store = $this->getMockBuilder(StoreInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['getBaseCurrencyCode'])
                ->getMockForAbstractClass();
            $this->storeManager->expects($this->atLeastOnce())
                ->method('getStore')
                ->willReturn($store);
            $store->expects($this->atLeastOnce())
                ->method('getBaseCurrencyCode')
                ->willReturn('USD');
            $currency = $this->createMock(Currency::class);
            $this->localeCurrency->expects($this->atLeastOnce())
                ->method('getCurrency')
                ->with('USD')
                ->willReturn($currency);
            $currency->expects($this->atLeastOnce())
                ->method('toCurrency')
                ->with(12, ['display' => Currency::NO_SYMBOL])
                ->willReturn('12');
        }
        $tierPrice->expects($this->exactly(count($skus)))
            ->method('getSku')
            ->willReturnOnConsecutiveCalls(...$skus);
        $this->tierPriceFetcher->expects($this->once())
            ->method('fetch')
            ->with($sharedCatalog, $skus)
            ->willReturn(new \ArrayIterator(array_fill(0, count($skus), $tierPrice)));
        $storage->expects($this->once())
            ->method('setTierPrices')
            ->with(array_fill_keys($skus, [$price]));

        $this->model->populateProductTierPrices([$product, $product, $product], $sharedCatalogId, $storage);
    }

    /**
     * Data provider for populateProductTierPrices method.
     *
     * @return array
     */
    public function populateProductTierPricesDataProvider(): array
    {
        return [
            [
                'fixed',
                [
                    'qty' => 1,
                    'website_id' => 3,
                    'value_type' => TierPriceInterface::PRICE_TYPE_FIXED,
                    'price' => '12',
                ]
            ],
            [
                'percent',
                [
                    'qty' => 1,
                    'website_id' => 3,
                    'value_type' => TierPriceInterface::PRICE_TYPE_DISCOUNT,
                    'percentage_value' => 12,
                ]
            ],
        ];
    }
}
