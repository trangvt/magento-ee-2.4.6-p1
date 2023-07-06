<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Catalog\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\StateException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Plugin\Catalog\Api\AdminProductRepositoryInterfacePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\SharedCatalog\Plugin\Catalog\Api\AdminProductRepositoryInterfacePlugin class.
 */
class AdminProductRepositoryInterfacePluginTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var ProductItemRepositoryInterface|MockObject
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var ProductItemInterface|MockObject
     */
    private $productItem;

    /**
     * @var ProductInterface|MockObject
     */
    private $product;

    /**
     * @var AdminProductRepositoryInterfacePlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogProductItemRepository = $this->getMockBuilder(
            ProductItemRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResult = $this->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productItem = $this->getMockBuilder(ProductItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->product->expects($this->once())->method('getSku')->willReturn('sku');
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with(ProductItemInterface::SKU, 'sku');
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$this->productItem]);
        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            AdminProductRepositoryInterfacePlugin::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'sharedCatalogProductItemRepository' => $this->sharedCatalogProductItemRepository
            ]
        );
    }

    /**
     * Test aroundDelete method.
     *
     * @return void
     */
    public function testAroundDelete()
    {
        $closure = function () {
            return;
        };
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('delete')->with($this->productItem)
            ->willReturn(true);

        $this->plugin->aroundDelete($this->productRepositoryMock, $closure, $this->product);
    }

    /**
     * Test aroundDelete method throws Magento\Framework\Exception\StateException exception.
     *
     * @return void
     */
    public function testAroundDeleteFailed()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage('Some internal exception message.');
        $closure = function () {
            return;
        };
        $this->sharedCatalogProductItemRepository->expects($this->once())
            ->method('delete')
            ->willThrowException(
                new StateException(__('Some internal exception message.'))
            );

        $this->plugin->aroundDelete($this->productRepositoryMock, $closure, $this->product);
    }
}
