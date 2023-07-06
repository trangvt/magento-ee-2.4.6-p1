<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Model\ProductSkuLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductSkuLocator model.
 */
class ProductSkuLocatorTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductSkuLocator
     */
    private $productSkuLocator;

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
        $this->searchCriteriaBuilder = $this
            ->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->productSkuLocator = $objectManager->getObject(
            ProductSkuLocator::class,
            [
                'productRepository' => $this->productRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    /**
     * Test for getProductSkus method.
     *
     * @return void
     */
    public function testGetProductSkus()
    {
        $productId = 1;
        $productSku = 'SKU01';
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')->with('entity_id', [$productId], 'in')->willReturnSelf();
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
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $product->expects($this->once())->method('getId')->wilLReturn($productId);
        $product->expects($this->once())->method('getSku')->wilLReturn($productSku);
        $this->assertEquals([$productId => $productSku], $this->productSkuLocator->getProductSkus([$productId]));
    }
}
