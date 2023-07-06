<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Model\ProductItemRepository;
use Magento\SharedCatalog\Model\ProductManagement;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test ProductManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductManagementTest extends TestCase
{
    /**
     * @var ProductManagement
     */
    private $productManagement;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var SharedCatalogInvalidation|MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    private $sharedCatalogCategoryManagement;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var SearchCriteria|MockObject
     */
    private $searchCriteria;

    /**
     * @var ProductItemRepositoryInterface
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var ProductItemInterface|MockObject
     */
    private $sharedCatalogProduct;

    /**
     * @var Product|MockObject
     */
    private $product;

    /**
     * @var ProductItemManagementInterface|MockObject
     */
    private $sharedCatalogProductItemManagement;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->sharedCatalogInvalidation = $this->createMock(SharedCatalogInvalidation::class);
        $this->sharedCatalogCategoryManagement = $this->createMock(CategoryManagementInterface::class);
        $this->sharedCatalog = $this->createMock(SharedCatalogInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria = $this->createMock(SearchCriteria::class);
        $this->sharedCatalogProductItemRepository = $this->createMock(ProductItemRepository::class);
        $this->sharedCatalogProduct = $this->createMock(ProductItemInterface::class);
        $this->product = $this->createMock(Product::class);
        $this->sharedCatalogProductItemManagement = $this->createMock(ProductItemManagementInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->productManagement = $objectManagerHelper->getObject(
            ProductManagement::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
                'sharedCatalogCategoryManagement' => $this->sharedCatalogCategoryManagement,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository,
                'sharedCatalogProductItemManagement' => $this->sharedCatalogProductItemManagement,
                'productRepository' => $this->productRepository,
            ]
        );
    }

    /**
     * Prepare SharedCatalogProductItemRepository mock.
     *
     * @return void
     */
    private function prepareSharedCatalogProductItemRepository()
    {
        $sharedCatalogProductSearchResults = $this
            ->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogProducts = [$this->sharedCatalogProduct];
        $sharedCatalogProductSearchResults
            ->expects($this->atLeastOnce())->method('getItems')
            ->willReturn($sharedCatalogProducts);
        $sharedCatalogProductSearchResults->expects($this->any())->method('getTotalCount')->willReturn(1);
        $this->sharedCatalogProductItemRepository
            ->expects($this->atLeastOnce())->method('getList')
            ->willReturn($sharedCatalogProductSearchResults);
    }

    /**
     * Test getProducts().
     *
     * @return void
     */
    public function testGetProducts()
    {
        $sharedCatalogId = 234;

        $customerGroupId = 223;
        $this->sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);

        $this->sharedCatalogInvalidation->expects($this->once())->method('checkSharedCatalogExist')
            ->willReturn($this->sharedCatalog);

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($this->searchCriteria);
        $this->searchCriteria->expects($this->once())->method('setCurrentPage')->with(1);

        $sku = 'HSVC347458';
        $this->sharedCatalogProduct->expects($this->once())->method('getSku')->willReturn($sku);

        $this->prepareSharedCatalogProductItemRepository();

        $result = $this->productManagement->getProducts($sharedCatalogId);

        $this->assertEquals([$sku], $result);
    }

    /**
     * Test assignProducts().
     *
     * @return void
     */
    public function testAssignProducts()
    {
        $sharedCatalogId = 234;
        $products = [$this->product];

        $this->sharedCatalogInvalidation->expects($this->once())->method('checkSharedCatalogExist')
            ->willReturn($this->sharedCatalog);

        $customerGroupId = 223;
        $this->sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->sharedCatalog->expects($this->once())->method('getId')->willReturn($sharedCatalogId);
        $sharedCatalogType = SharedCatalogInterface::TYPE_PUBLIC;
        $this->sharedCatalog->expects($this->once())->method('getType')->willReturn($sharedCatalogType);

        $sharedCatalogCategoryId = 83;
        $sharedCatalogCategoryIds = [$sharedCatalogCategoryId];
        $this->sharedCatalogCategoryManagement->expects($this->once())->method('getCategories')
            ->willReturn($sharedCatalogCategoryIds);

        $sku = 'FGJFG4554345';
        $this->product->method('getSku')
            ->willReturn($sku);
        $this->productRepository->expects($this->once())
            ->method('get')
            ->with($sku)
            ->willReturn($this->product);

        $this->sharedCatalogProductItemManagement->expects($this->atLeastOnce())->method('addItems')->willReturnSelf();

        $this->assertTrue($this->productManagement->assignProducts($sharedCatalogId, $products));
    }

    /**
     * Test unassignProducts().
     *
     * @return void
     */
    public function testUnassignProducts()
    {
        $sharedCatalogId = 234;
        $products = [$this->product];
        $sku = 'FGJFG4554345';
        $skus = [$sku];

        $this->sharedCatalogInvalidation->expects($this->once())->method('checkSharedCatalogExist')
            ->willReturn($this->sharedCatalog);

        $this->sharedCatalogInvalidation->expects($this->once())->method('validateUnassignProducts')
            ->willReturn($skus);

        $customerGroupId = 223;
        $this->sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalogType = SharedCatalogInterface::TYPE_PUBLIC;
        $this->sharedCatalog->expects($this->once())->method('getType')->willReturn($sharedCatalogType);

        //delete items
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->sharedCatalogProduct->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);

        $this->prepareSharedCatalogProductItemRepository();
        $sharedCatalogItemIsDeleted = true;
        $this->sharedCatalogProductItemRepository->expects($this->atLeastOnce())->method('deleteItems')
            ->willReturn($sharedCatalogItemIsDeleted);

        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('cleanCacheByTag');
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('invalidateIndexRegistryItem');

        $this->assertTrue($this->productManagement->unassignProducts($sharedCatalogId, $products));
    }

    /**
     * Test reassignProducts().
     *
     * @return void
     */
    public function testReassignProducts()
    {
        $sku = 'FGJFG4554345';
        $skus = [$sku];

        $customerGroupId = 223;
        $this->sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalogType = SharedCatalogInterface::TYPE_PUBLIC;
        $this->sharedCatalog->expects($this->once())->method('getType')->willReturn($sharedCatalogType);

        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->sharedCatalogProduct->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);

        $this->prepareSharedCatalogProductItemRepository();
        $sharedCatalogItemIsDeleted = true;
        $this->sharedCatalogProductItemRepository->expects($this->atLeastOnce())->method('deleteItems')
            ->willReturn($sharedCatalogItemIsDeleted);

        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('cleanCacheByTag');
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())->method('invalidateIndexRegistryItem');

        $this->sharedCatalogProductItemManagement->expects($this->atLeastOnce())->method('addItems')->willReturnSelf();

        $result = $this->productManagement->reassignProducts($this->sharedCatalog, $skus);
        $this->assertEquals($this->productManagement, $result);
    }
}
