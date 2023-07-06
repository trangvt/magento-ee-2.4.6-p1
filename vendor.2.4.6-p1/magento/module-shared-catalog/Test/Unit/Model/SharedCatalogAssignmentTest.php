<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\SharedCatalogAssignment;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\SharedCatalog\Model\SharedCatalogAssignment class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SharedCatalogAssignmentTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ProductInterface|MockObject
     */
    private $product;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private $productManagement;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var ProductItemRepositoryInterface|MockObject
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var SharedCatalogInvalidation|MockObject
     */
    private $sharedCatalogInvalidation;

    /**
     * @var CollectionFactory|MockObject
     */
    private $productCollectionFactory;

    /**
     * @var SharedCatalogAssignment
     */
    private $sharedCatalogAssignment;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds'])
            ->getMockForAbstractClass();
        $this->productManagement = $this->getMockBuilder(ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository = $this->getMockBuilder(
            SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository = $this->getMockBuilder(
            ProductItemRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogInvalidation = $this->getMockBuilder(
            SharedCatalogInvalidation::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogAssignment = $this->objectManagerHelper->getObject(
            SharedCatalogAssignment::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'productRepository' => $this->productRepository,
                'productManagement' => $this->productManagement,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository,
                'sharedCatalogInvalidation' => $this->sharedCatalogInvalidation,
                'productCollectionFactory' => $this->productCollectionFactory
            ]
        );
    }

    /**
     * Test assignProductsForCategories method.
     *
     * @return void
     */
    public function testAssignProductsForCategories()
    {
        $sharedCatalogId = 3;
        $assignCategoriesIds = [12, 15];
        $productsSearchResult = $this->getMockBuilder(ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('category_id', $assignCategoriesIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->productRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($productsSearchResult);
        $productsSearchResult->expects($this->once())->method('getItems')->willReturn([$this->product]);
        $this->productManagement->expects($this->once())
            ->method('assignProducts')
            ->with($sharedCatalogId, [$this->product])
            ->willReturn(true);
        $this->sharedCatalogAssignment->assignProductsForCategories($sharedCatalogId, $assignCategoriesIds);
    }

    /**
     * Test unassignProductsForCategories method.
     *
     * @return void
     */
    public function testUnassignProductsForCategories()
    {
        $sharedCatalogId = 3;
        $assignCategoriesIds = [12, 15];
        $unAssignCategoriesIds = [12, 20];
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult = $this->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds', 'getSku'])
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository->expects($this->once())
            ->method('get')
            ->with($sharedCatalogId)
            ->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn(2);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('customer_group_id', 2)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$productItem]);
        $productItem->expects($this->once())->method('getSku')->willReturn('sku');
        $this->sharedCatalogInvalidation->expects($this->atLeastOnce())
            ->method('checkProductExist')
            ->willReturn($productItem);
        $productItem->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn([]);
        $this->productManagement->expects($this->once())
            ->method('unassignProducts')
            ->with($sharedCatalogId, [$productItem])
            ->willReturn(true);
        $this->sharedCatalogAssignment->unassignProductsForCategories(
            $sharedCatalogId,
            $unAssignCategoriesIds,
            $assignCategoriesIds
        );
    }

    /**
     * Test getAssignCategoryIdsByProductSkus method.
     *
     * @return void
     */
    public function testGetAssignCategoryIdsByProductSkus()
    {
        $assignProductsSkus = ['sku_1', 'sku_2'];
        $categoryIds = [9, 13];

        $productsCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productsCollection->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->with('sku', ['in' => $assignProductsSkus])
            ->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('setPageSize')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getLastPageNumber')->willReturn(1);
        $productsCollection->expects($this->atLeastOnce())->method('setCurPage')->with(1)->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('addCategoryIds')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$this->product]);
        $this->product->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn($categoryIds);
        $productsCollection->expects($this->atLeastOnce())->method('clear')->willReturnSelf();
        $this->productCollectionFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($productsCollection);
        $this->assertSame(
            $categoryIds,
            $this->sharedCatalogAssignment->getAssignCategoryIdsByProductSkus($assignProductsSkus)
        );
    }

    /**
     * Test getAssignProductSkusByCategoryIds method.
     *
     * @return void
     */
    public function testGetAssignProductSkusByCategoryIds()
    {
        $assignCategoriesIds = [12, 15];
        $productSku = 'sku_1';

        $productsCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productsCollection->expects($this->atLeastOnce())->method('addCategoriesFilter')
            ->with(['in' => $assignCategoriesIds])
            ->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getItems')
            ->willReturn([$this->product]);
        $this->productCollectionFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($productsCollection);
        $this->product->expects($this->once())->method('getSku')->willReturn($productSku);
        $this->assertSame(
            [$productSku],
            $this->sharedCatalogAssignment->getAssignProductSkusByCategoryIds($assignCategoriesIds)
        );
    }

    /**
     * Test getAssignProductsByCategoryIds method.
     *
     * @return void
     */
    public function testGetAssignProductsByCategoryIds()
    {
        $assignCategoriesIds = [12, 15];
        $productSku = 'sku_1';
        $productsCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productsCollection->expects($this->atLeastOnce())->method('addCategoriesFilter')
            ->withConsecutive([['in' => $assignCategoriesIds[0]]], [['in' => $assignCategoriesIds[1]]])
            ->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('setPageSize')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getLastPageNumber')->willReturn(1);
        $productsCollection->expects($this->atLeastOnce())->method('setCurPage')->with(1)->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('addCategoryIds')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$this->product]);
        $productsCollection->expects($this->atLeastOnce())->method('clear')->willReturnSelf();
        $this->product->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn($assignCategoriesIds);
        $this->product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->productCollectionFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($productsCollection);
        $this->assertSame(
            [
                'skus' => [$productSku => $productSku],
                'category_ids' => $assignCategoriesIds
            ],
            $this->sharedCatalogAssignment->getAssignProductsByCategoryIds($assignCategoriesIds)
        );
    }

    /**
     * Test getProductSkusToUnassign method.
     *
     * @return void
     */
    public function testGetProductSkusToUnassign()
    {
        $unassignCategoriesIds = [15, 20];
        $assignedCategoriesIds = [15, 21];
        $productSku = ['sku_1' => 'sku_1'];

        $productsCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productsCollection->expects($this->atLeastOnce())->method('addCategoriesFilter')
            ->withConsecutive([['in' => $unassignCategoriesIds[0]]], [['in' => $unassignCategoriesIds[1]]])
            ->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('setPageSize')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getLastPageNumber')->willReturn(1);
        $productsCollection->expects($this->atLeastOnce())->method('setCurPage')->with(1)->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('addCategoryIds')->willReturnSelf();
        $productsCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$this->product]);
        $productsCollection->expects($this->atLeastOnce())->method('clear')->willReturnSelf();
        $this->productCollectionFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($productsCollection);

        $this->product->expects($this->atLeastOnce())->method('getCategoryIds')->willReturn([]);
        $this->product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku['sku_1']);
        $this->assertSame(
            $productSku,
            $this->sharedCatalogAssignment->getProductSkusToUnassign($unassignCategoriesIds, $assignedCategoriesIds)
        );
    }
}
