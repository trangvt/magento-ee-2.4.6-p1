<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Category\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\Repository;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for SharedCatalogInvalidation model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogInvalidationTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SharedCatalogInvalidation|MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ProductInterface|MockObject
     */
    private $product;

    /**
     * @var CollectionFactory|MockObject
     */
    private $productCollectionFactory;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|MockObject
     */
    private $indexerRegistry;

    /**
     * @var ConfigInterface|MockObject
     */
    private $permissionsConfig;

    /**
     * @var Repository|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var Product|MockObject
     */
    private $categoryProductIndexer;

    /**
     * @var Category|MockObject
     */
    private $catalogPermissionsCategoryIndexer;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['save', 'get', 'getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getSku', 'getCategoryIds'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productCollectionFactory = $this
            ->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->indexerRegistry = $this->getMockBuilder(IndexerRegistry::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionsConfig = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods(['isEnabled'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogRepository = $this->getMockBuilder(Repository::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->categoryProductIndexer = $this->getMockBuilder(Product::class)
            ->setMethods(['invalidate'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogPermissionsCategoryIndexer = $this
            ->getMockBuilder(Category::class)
            ->setMethods(['isScheduled', 'reindexList'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogInvalidation = $this->objectManagerHelper->getObject(
            SharedCatalogInvalidation::class,
            [
                'productRepository' => $this->productRepository,
                'productCollectionFactory' => $this->productCollectionFactory,
                'eventManager' => $this->eventManager,
                'indexerRegistry' => $this->indexerRegistry,
                'permissionsConfig' => $this->permissionsConfig,
                'sharedCatalogRepository' => $this->sharedCatalogRepository
            ]
        );
    }

    /**
     * Prepare IndexerRegistry mock.
     *
     * @return void
     */
    private function prepareIndexerRegistry()
    {
        $mapForMethodGet = [
            ['catalog_category_product', $this->categoryProductIndexer],
            ['catalogpermissions_category', $this->catalogPermissionsCategoryIndexer]
        ];
        $this->indexerRegistry->expects($this->exactly(1))->method('get')->willReturnMap($mapForMethodGet);
    }

    /**
     * Test for cleanCacheByTag().
     *
     * @return void
     */
    public function testCleanCacheByTag()
    {
        $sku = 'test_sku_1';

        $this->productRepository->expects($this->exactly(1))->method('get')->willReturn($this->product);

        $this->eventManager->expects($this->exactly(1))->method('dispatch');

        $this->assertNull($this->sharedCatalogInvalidation->cleanCacheByTag($sku));
    }

    /**
     * Test for invalidateIndexRegistryItem().
     *
     * @return void
     */
    public function testInvalidateIndexRegistryItem()
    {
        $this->categoryProductIndexer->expects($this->exactly(1))->method('invalidate');

        $this->prepareIndexerRegistry();

        $this->assertNull($this->sharedCatalogInvalidation->invalidateIndexRegistryItem());
    }

    /**
     * Test for validateAssignProducts().
     *
     * @return void
     */
    public function testValidateAssignProducts()
    {
        $categoryId = 236;
        $categoryIds = [$categoryId];

        $productSku = 'ASDF23526';
        $this->product->expects($this->any())->method('getSku')->willReturn($productSku);
        $this->product->expects($this->any())->method('getCategoryIds')->willReturn($categoryIds);

        $products = [$this->product];

        $expected = [$productSku];
        $result = $this->sharedCatalogInvalidation->validateAssignProducts($products, $categoryIds);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test validateAssignProducts() with Exception.
     *
     * @return void
     */
    public function testValidateAssignProductsWithException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $categoryId = 236;
        $productsCategoryId = 356;
        $categoryIds = [$categoryId];

        $productSku = 'ASDF23526';
        $this->product->expects($this->any())->method('getSku')->willReturn($productSku);
        $productCategoryIds = [$productsCategoryId];
        $this->product->expects($this->any())->method('getCategoryIds')->willReturn($productCategoryIds);

        $products = [$this->product];

        $this->sharedCatalogInvalidation->validateAssignProducts($products, $categoryIds);
    }

    /**
     * Test for validateUnassignProducts().
     *
     * @return void
     */
    public function testValidateUnassignProducts()
    {
        $productSku = 'ASDF23526';
        $this->product->expects($this->exactly(2))->method('getSku')->willReturn($productSku);

        $products = [$this->product];

        $this->productRepository->expects($this->exactly(1))->method('get')->willReturn($this->product);

        $expected = [$productSku];
        $result = $this->sharedCatalogInvalidation->validateUnassignProducts($products);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for checkProductExist().
     *
     * @return void
     */
    public function testCheckProductExist()
    {
        $productSku = 'ASDF23526';

        $this->productRepository->expects($this->exactly(1))->method('get')->willReturn($this->product);

        $expected = $this->product;
        $result = $this->sharedCatalogInvalidation->checkProductExist($productSku);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test checkProductExist() with Exception.
     *
     * @return void
     */
    public function testCheckProductExistWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $productSku = 'ASDF23526';

        $exception = new NoSuchEntityException();
        $this->productRepository->expects($this->exactly(1))->method('get')->willThrowException($exception);

        $expected = $this->product;
        $result = $this->sharedCatalogInvalidation->checkProductExist($productSku);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for reindexCatalogPermissions().
     *
     * @return void
     */
    public function testReindexCatalogPermissions()
    {
        $reindexCategoryIds = [23];

        $isEnabled = true;
        $this->permissionsConfig->expects($this->exactly(1))->method('isEnabled')->willReturn($isEnabled);
        $this->catalogPermissionsCategoryIndexer->expects($this->exactly(1))->method('reindexList');

        $this->prepareIndexerRegistry();

        $this->assertNull($this->sharedCatalogInvalidation->reindexCatalogPermissions($reindexCategoryIds));
    }

    /**
     * Test for checkSharedCatalogExist().
     *
     * @return void
     */
    public function testCheckSharedCatalogExist()
    {
        $sharedCatalogId = 23463;

        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->willReturn($this->sharedCatalog);

        $result = $this->sharedCatalogInvalidation->checkSharedCatalogExist($sharedCatalogId);
        $this->assertEquals($this->sharedCatalog, $result);
    }

    /**
     * Test for checkSharedCatalogExist() with Exception.
     *
     * @return void
     */
    public function testCheckSharedCatalogExistWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $sharedCatalogId = 23463;

        $exception = new NoSuchEntityException();
        $this->sharedCatalogRepository->expects($this->exactly(1))->method('get')->willThrowException($exception);

        $this->sharedCatalogInvalidation->checkSharedCatalogExist($sharedCatalogId);
    }
}
