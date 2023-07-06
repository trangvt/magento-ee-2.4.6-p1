<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductOptionInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Model\RequisitionListItem\ProductExtractor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductExtractor model.
 */
class ProductExtractorTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ProductCustomOptionRepositoryInterface|MockObject
     */
    private $productOptionRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductExtractor
     */
    private $productExtractor;

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
        $this->productOptionRepository = $this
            ->getMockBuilder(ProductCustomOptionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->productExtractor = $objectManager->getObject(
            ProductExtractor::class,
            [
                'productRepository' => $this->productRepository,
                'productOptionRepository' => $this->productOptionRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    /**
     * Test for extract method.
     *
     * @return void
     */
    public function testExtract()
    {
        $productSku = 'SKU01';
        $websiteId = 1;

        $this->searchCriteriaBuilder->expects($this->exactly(2))->method('addFilter')
            ->withConsecutive(
                [ProductInterface::SKU, [$productSku], 'in'],
                ['website_id', $websiteId, 'in']
            )->wilLReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getProductOptionsCollection', 'addOption', 'getSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $option = $this->getMockBuilder(ProductOptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productOptionRepository->expects($this->once())
            ->method('getProductOptions')->with($product)->willReturn([$option]);
        $product->expects($this->once())->method('setOptions')->with([$option])->willReturnSelf();
        $product->expects($this->once())->method('getSku')->willReturn($productSku);
        $this->assertEquals(
            [$productSku => $product],
            $this->productExtractor->extract([$productSku], $websiteId)
        );
    }
}
