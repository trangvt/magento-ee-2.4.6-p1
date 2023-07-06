<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Form\Storage\Wizard;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\Wizard\Builder;
use Magento\SharedCatalog\Model\Price\ProductTierPriceLoader;
use Magento\SharedCatalog\Model\SharedCatalogProductsLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model Form\Storage\Wizard\Builder.
 */
class BuilderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SharedCatalogProductsLoader|MockObject
     */
    private $sharedCatalogProductsLoader;

    /**
     * @var ProductTierPriceLoader|MockObject
     */
    private $productTierPriceLoader;

    /**
     * @var CategoryManagementInterface|MockObject
     */
    private $sharedCatalogCategoryManagement;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sharedCatalogProductsLoader = $this
            ->getMockBuilder(SharedCatalogProductsLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTierPriceLoader = $this
            ->getMockBuilder(ProductTierPriceLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogCategoryManagement = $this
            ->getMockBuilder(CategoryManagementInterface::class)
            ->setMethods(['getCategories'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->builder = $this->objectManagerHelper->getObject(
            Builder::class,
            [
                'sharedCatalogProductsLoader' => $this->sharedCatalogProductsLoader,
                'productTierPriceLoader' => $this->productTierPriceLoader,
                'sharedCatalogCategoryManagement' => $this->sharedCatalogCategoryManagement
            ]
        );
    }

    /**
     * Test for build().
     *
     * @return void
     */
    public function testBuild()
    {
        $categoryIds = [23];
        $customerGroupId = 23;
        $productSkus = ['sku_1', 'sku_2'];
        $wizardStorage = $this->getMockBuilder(Wizard::class)
            ->setMethods(['assignProducts', 'assignCategories'])
            ->disableOriginalConstructor()
            ->getMock();
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getCustomerGroupId', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->sharedCatalogProductsLoader->expects($this->once())
            ->method('getAssignedProductsSkus')
            ->with($customerGroupId)
            ->willReturn($productSkus);
        $this->productTierPriceLoader->expects($this->once())
            ->method('populateTierPrices')
            ->with($productSkus, 1, $wizardStorage);
        $wizardStorage->expects($this->once())->method('assignProducts')->with($productSkus)->willReturnSelf();
        $sharedCatalog->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->sharedCatalogCategoryManagement->expects($this->once())
            ->method('getCategories')
            ->willReturn($categoryIds);
        $wizardStorage->expects($this->once())->method('assignCategories')->with($categoryIds)->willReturnSelf();

        $this->assertEquals($wizardStorage, $this->builder->build($wizardStorage, $sharedCatalog));
    }
}
