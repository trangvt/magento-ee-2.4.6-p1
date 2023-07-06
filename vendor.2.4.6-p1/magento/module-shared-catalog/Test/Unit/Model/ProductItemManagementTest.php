<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Api\SharedCatalogManagementInterface;
use Magento\SharedCatalog\Model\ProductItemFactory;
use Magento\SharedCatalog\Model\ProductItemManagement;
use Magento\SharedCatalog\Model\ProductItemRepository;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem;
use Magento\SharedCatalog\Model\SharedCatalogProductsLoader;
use Magento\SharedCatalog\Model\TierPriceManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductItemManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductItemManagementTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductItemRepositoryInterface|MockObject
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var ProductItemFactory|MockObject
     */
    private $sharedCatalogProductItemFactory;

    /**
     * @var TierPriceManagement|MockObject
     */
    private $sharedCatalogTierPriceManagement;

    /**
     * @var SharedCatalogManagementInterface|MockObject
     */
    private $sharedCatalogManagement;

    /**
     * @var SharedCatalogProductsLoader|MockObject
     */
    private $sharedCatalogProductsLoader;

    /**
     * @var ProductItem|MockObject
     */
    private $productItemResource;

    /**
     * @var ProductItemManagement
     */
    private $productItemManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogProductItemRepository = $this->getMockBuilder(ProductItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogProductItemFactory = $this->getMockBuilder(ProductItemFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogTierPriceManagement = $this
            ->getMockBuilder(TierPriceManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogManagement = $this
            ->getMockBuilder(SharedCatalogManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductsLoader = $this
            ->getMockBuilder(SharedCatalogProductsLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productItemResource = $this
            ->getMockBuilder(ProductItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productItemManagement = (new ObjectManager($this))->getObject(
            ProductItemManagement::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository,
                'sharedCatalogProductItemFactory' => $this->sharedCatalogProductItemFactory,
                'sharedCatalogTierPriceManagement' => $this->sharedCatalogTierPriceManagement,
                'sharedCatalogManagement' => $this->sharedCatalogManagement,
                'sharedCatalogProductsLoader' => $this->sharedCatalogProductsLoader,
                'productItemResource' => $this->productItemResource,
                'batchSize' => 2,
            ]
        );
    }

    /**
     * Test for deleteItems method.
     *
     * @return void
     */
    public function testDeleteItems(): void
    {
        $customerGroupId = 1;
        $productSkus = ['SKU1', 'SKU2'];
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn(SharedCatalogInterface::TYPE_PUBLIC);
        $this->searchCriteriaBuilder->expects($this->exactly(4))
            ->method('addFilter')
            ->withConsecutive(
                ['customer_group_id', $customerGroupId, 'eq'],
                ['sku', $productSkus, 'in'],
                ['customer_group_id', 0, 'eq'],
                ['sku', $productSkus, 'in']
            )->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->willReturnSelf();
        $searchCriteria->expects($this->atLeastOnce())
            ->method('setCurrentPage')
            ->with(1)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('create')
            ->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository->expects($this->exactly(2))
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->exactly(2))
            ->method('getItems')
            ->willReturn(
                [
                    $productItem,
                    $productItem
                ]
            );
        $searchResults->expects($this->any())
            ->method('getTotalCount')
            ->willReturn(2);
        $productItem->expects($this->exactly(2))
            ->method('getSku')
            ->willReturnOnConsecutiveCalls($productSkus[0], $productSkus[1]);
        $this->sharedCatalogTierPriceManagement->expects($this->once())
            ->method('deleteProductTierPrices')
            ->with($sharedCatalog, $productSkus, true);
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('deleteItems')
            ->with([$productItem, $productItem]);

        $this->productItemManagement->deleteItems($sharedCatalog, $productSkus);
    }

    /**
     * Test for updateTierPrices method.
     *
     * @return void
     */
    public function testUpdateTierPrices(): void
    {
        $productSku = 'SKU1';
        $tierPricesData = ['tier_prices_data'];
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->exactly(2))
            ->method('getSku')
            ->willReturn($productSku);
        $this->sharedCatalogTierPriceManagement->expects($this->once())
            ->method('deleteProductTierPrices')
            ->with($sharedCatalog, [$productSku]);
        $this->sharedCatalogTierPriceManagement
            ->expects($this->once())
            ->method('updateProductTierPrices')->with($sharedCatalog, $productSku, $tierPricesData);
        $this->productItemManagement->updateTierPrices($sharedCatalog, $product, $tierPricesData);
    }

    /**
     * Test for deleteTierPricesBySku method.
     *
     * @return void
     */
    public function testDeleteTierPricesBySku(): void
    {
        $productSkus = ['SKU1', 'SKU2'];
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogTierPriceManagement->expects($this->once())
            ->method('deleteProductTierPrices')
            ->with($sharedCatalog, $productSkus);
        $this->productItemManagement->deleteTierPricesBySku($sharedCatalog, $productSkus);
    }

    /**
     * Test for addItems method.
     *
     * @return void
     */
    public function testAddItems(): void
    {
        $customerGroupId = 1;
        $productSkus = ['SKU1', 'SKU2'];
        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(
                ['customer_group_id', $customerGroupId, 'eq'],
                ['sku', array_unique($productSkus), 'in']
            )
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->willReturnSelf();
        $searchCriteria->expects($this->atLeastOnce())
            ->method('setCurrentPage')
            ->with(1)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn([$productItem]);
        $searchResults->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(1);
        $productItem->expects($this->once())
            ->method('getSku')
            ->willReturn($productSkus[1]);
        $this->productItemResource->expects($this->once())
            ->method('createItems')
            ->with([$productSkus[0]], $customerGroupId);
        $this->productItemManagement->addItems($customerGroupId, $productSkus);
    }

    /**
     * Test for addItems method with LocalizedException.
     *
     * @return void
     */
    public function testAddItemsWithLocalizedException(): void
    {
        $this->expectExceptionMessage("Cannot load product items for shared catalog");
        $this->expectException(LocalizedException::class);
        $customerGroupId = 1;
        $productSkus = ['SKU1', 'SKU2'];
        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(['customer_group_id', $customerGroupId, 'eq'], ['sku', $productSkus, 'in'])
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $exception = new \InvalidArgumentException('test');
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willThrowException($exception);

        $this->productItemManagement->addItems($customerGroupId, $productSkus);
    }

    /**
     * Test for saveItem method.
     *
     * @return void
     */
    public function testSaveItem(): void
    {
        $customerGroupId = 1;
        $productSku = 'SKU1';
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemFactory->expects($this->once())
            ->method('create')
            ->willReturn($productItem);
        $productItem->expects($this->once())
            ->method('setSku')
            ->with($productSku)
            ->willReturnSelf();
        $productItem->expects($this->once())
            ->method('setCustomerGroupId')
            ->with($customerGroupId)
            ->willReturnSelf();
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('save')
            ->with($productItem)
            ->willReturn(2);
        $this->productItemManagement->saveItem($productSku, $customerGroupId);
    }

    /**
     * Test for deletePricesForPublicCatalog method.
     *
     * @return void
     */
    public function testDeletePricesForPublicCatalog(): void
    {
        $productSkus = ['SKU1', 'SKU2'];
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('customer_group_id', 0, 'eq')
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->willReturnSelf();
        $searchCriteria->expects($this->atLeastOnce())
            ->method('setCurrentPage')
            ->with(1)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn(
                [
                    $productItem,
                    $productItem
                ]
            );
        $searchResults->expects($this->any())
            ->method('getTotalCount')
            ->willReturn(2);
        $productItem->expects($this->exactly(2))
            ->method('getSku')
            ->willReturnOnConsecutiveCalls($productSkus[0], $productSkus[1]);
        $this->sharedCatalogTierPriceManagement->expects($this->once())
            ->method('deletePublicTierPrices')
            ->with($productSkus);
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('deleteItems')
            ->with([$productItem, $productItem]);
        $this->productItemManagement->deletePricesForPublicCatalog();
    }

    /**
     * Test for addPricesForPublicCatalog method.
     *
     * @return void
     */
    public function testAddPricesForPublicCatalog(): void
    {
        $customerGroupId = 1;
        $productSkus = ['SKU1', 'SKU2'];
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogManagement->expects($this->once())
            ->method('getPublicCatalog')
            ->willReturn($sharedCatalog);
        $sharedCatalog->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $this->sharedCatalogProductsLoader->expects($this->once())
            ->method('getAssignedProductsSkus')
            ->with($customerGroupId)
            ->willReturn($productSkus);
        $this->sharedCatalogTierPriceManagement->expects($this->once())
            ->method('addPricesForPublicCatalog')
            ->with($customerGroupId, $productSkus);
        $this->searchCriteriaBuilder->expects($this->exactly(2))
            ->method('addFilter')
            ->withConsecutive(['customer_group_id', 0, 'eq'], ['sku', $productSkus, 'in'])
            ->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $searchResults = $this
            ->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn([$productItem]);
        $productItem->expects($this->once())
            ->method('getSku')
            ->willReturn($productSkus[1]);
        $this->productItemResource->expects($this->once())
            ->method('createItems')
            ->with([$productSkus[0]], 0);
        $this->productItemManagement->addPricesForPublicCatalog();
    }
}
