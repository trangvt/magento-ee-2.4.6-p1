<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Form\Storage;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\Form\Storage\DiffProcessor;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\ScheduleBulk;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DiffProcessor model.
 */
class DiffProcessorTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DiffProcessor
     */
    private $diffProcessor;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    private $categoryManagementMock;

    /**
     * @var ProductManagementInterface|MockObject
     */
    private $productManagementMock;

    /**
     * @var ScheduleBulk|MockObject
     */
    private $scheduleBulkMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->categoryManagementMock = $this->getMockBuilder(CategoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productManagementMock = $this->getMockBuilder(ProductManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scheduleBulkMock = $this->getMockBuilder(ScheduleBulk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->diffProcessor = $this->objectManagerHelper->getObject(
            DiffProcessor::class,
            [
                'categoryManagement' => $this->categoryManagementMock,
                'productManagement' => $this->productManagementMock,
                'scheduleBulk' => $this->scheduleBulkMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetDiff()
    {
        $sharedCatalogId = 1;
        $result = [
            'pricesChanged' => false,
            'categoriesChanged' => false,
            'productsChanged' => false
        ];
        $categories = [1];
        $products = ['sku1'];
        $prices = [1];

        $storageMock = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryManagementMock->expects($this->once())->method('getCategories')->with($sharedCatalogId)
            ->willReturn($categories);
        $this->productManagementMock->expects($this->once())->method('getProducts')->with($sharedCatalogId)
            ->willReturn($products);
        $storageMock->expects($this->once())->method('getTierPrices')->willReturn($prices);
        $storageMock->expects($this->once())->method('getUnassignedProductSkus')->willReturn([]);
        $this->scheduleBulkMock->expects($this->once())->method('filterUnchangedPrices')->willReturn([]);
        $storageMock->expects($this->once())->method('getAssignedCategoriesIds')->willReturn($categories);
        $storageMock->expects($this->once())->method('getAssignedProductSkus')->willReturn($products);

        $this->assertEquals($result, $this->diffProcessor->getDiff($storageMock, $sharedCatalogId));
    }
}
