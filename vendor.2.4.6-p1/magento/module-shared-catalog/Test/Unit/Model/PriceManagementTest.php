<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Model\PriceManagement;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PriceManagement model.
 */
class PriceManagementTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var PriceManagement
     */
    private $priceManagement;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ProductItemManagementInterface|MockObject
     */
    private $productItemManagement;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productItemManagement = $this
            ->getMockBuilder(ProductItemManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->priceManagement = $this->objectManagerHelper->getObject(
            PriceManagement::class,
            [
                'productRepository' => $this->productRepository,
                'productItemManagement' => $this->productItemManagement,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Test saveProductTierPrices().
     *
     * @return void
     */
    public function testSaveProductTierPrices()
    {
        $productId = 346;
        $priceData = [1, 2, 3];
        $prices = [$productId => $priceData];
        $this->prepareStoreManager();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())->method('getById')->willReturn($product);
        $this->productItemManagement->expects($this->once())->method('updateTierPrices')->willReturnSelf();
        $this->assertEquals(
            $this->priceManagement,
            $this->priceManagement->saveProductTierPrices($this->sharedCatalog, $prices)
        );
    }

    /**
     * Prepare StoreManager mock.
     *
     * @return void
     */
    private function prepareStoreManager()
    {
        $storeCode = 'test_store';
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())->method('getCode')->willReturn($storeCode);
        $this->storeManager->expects($this->atLeastOnce())
            ->method('getStore')->with(Store::DEFAULT_STORE_ID)->willReturn($store);
        $this->storeManager->expects($this->atLeastOnce())->method('setCurrentStore')->with($storeCode);
    }

    /**
     * Test deleteProductTierPrices().
     *
     * @return void
     */
    public function testDeleteProductTierPrices()
    {
        $sku = 'SDE323425';
        $skus = [$sku];
        $this->prepareStoreManager();
        $this->productItemManagement->expects($this->once())
            ->method('deleteTierPricesBySku')->with($this->sharedCatalog, $skus)->willReturnSelf();
        $this->assertEquals(
            $this->priceManagement,
            $this->priceManagement->deleteProductTierPrices($this->sharedCatalog, $skus)
        );
    }
}
