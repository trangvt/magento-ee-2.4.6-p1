<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Model\ProductItemRepository;
use Magento\SharedCatalog\Plugin\UpdateItemsSku;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\SharedCatalog\Plugin\UpdateItemsSku.
 */
class UpdateItemsSkuTest extends TestCase
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
     * @var SearchCriteria|MockObject
     */
    private $searchCriteria;

    /**
     * @var UpdateItemsSku
     */
    private $productPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogProductItemRepository = $this
            ->getMockBuilder(ProductItemRepository::class)
            ->setMethods(['getList', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->productPlugin = $objectManager->getObject(
            UpdateItemsSku::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository
            ]
        );
    }

    /**
     * Test for afterSave().
     *
     * @param string $sku
     * @param string $origSku
     * @param InvokedCount $call
     * @return void
     * @dataProvider afterSaveDataProvider
     */
    public function testAfterSave($sku, $origSku, $call)
    {
        $subject = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getOrigData')->with('sku')->willReturn($sku);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($origSku);
        $this->searchCriteriaBuilder->expects($call)->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($call)->method('create')->willReturn($this->searchCriteria);
        $sharedCatalogProductSearchResults = $this
            ->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->setMethods(['setSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogProductSearchResults
            ->expects($call)->method('getItems')
            ->willReturn([$productItem]);
        $this->sharedCatalogProductItemRepository
            ->expects($call)->method('getList')
            ->willReturn($sharedCatalogProductSearchResults);
        $productItem->expects($call)->method('setSku')->willReturnSelf();
        $this->sharedCatalogProductItemRepository
            ->expects($call)->method('getList')
            ->willReturn($sharedCatalogProductSearchResults);
        $this->assertEquals($product, $this->productPlugin->afterSave($subject, $product));
    }

    /**
     * Data provider for afterSave() test.
     *
     * @return array
     */
    public function afterSaveDataProvider()
    {
        return [
            ['test_sku_1', 'test_sku_1', $this->never()],
            ['test_sku_1', 'origSku' => 'test_orig_sku_1', $this->atLeastOnce()]
        ];
    }
}
