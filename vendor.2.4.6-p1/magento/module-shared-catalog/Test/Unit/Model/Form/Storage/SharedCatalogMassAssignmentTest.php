<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Form\Storage;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\Form\Storage\SharedCatalogMassAssignment;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Price\ProductTierPriceLoader;
use Magento\SharedCatalog\Model\SharedCatalogAssignment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SharedCatalogMassAssignment.
 */
class SharedCatalogMassAssignmentTest extends TestCase
{
    /**
     * @var ProductTierPriceLoader|MockObject
     */
    private $productTierPriceLoader;

    /**
     * @var SharedCatalogAssignment|MockObject
     */
    private $sharedCatalogAssignment;

    /**
     * @var SharedCatalogMassAssignment
     */
    private $sharedCatalogMassAssignment;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productTierPriceLoader = $this
            ->getMockBuilder(ProductTierPriceLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogAssignment = $this
            ->getMockBuilder(SharedCatalogAssignment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogMassAssignment = $objectManagerHelper->getObject(
            SharedCatalogMassAssignment::class,
            [
                'productTierPriceLoader' => $this->productTierPriceLoader,
                'sharedCatalogAssignment' => $this->sharedCatalogAssignment,
            ]
        );
    }

    /**
     * Unit test for assign().
     *
     * @return void
     */
    public function testAssign()
    {
        $sku = 'sku';
        $categoryIds = [2];
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collection = $this->getMockBuilder(Collection::class)
            ->setMethods(['setPageSize', 'getLastPageNumber', 'setCurPage', 'addCategoryIds', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->atLeastOnce())->method('setPageSize')->willReturnSelf();
        $collection->expects($this->atLeastOnce())->method('getLastPageNumber')->willReturn(1);
        $collection->expects($this->atLeastOnce())->method('setCurPage')->with(1)->willReturnSelf();
        $collection->expects($this->atLeastOnce())->method('addCategoryIds')->willReturnSelf();
        $collection->expects($this->atLeastOnce())->method('getItems')->willReturn([$product]);
        $storage->expects($this->atLeastOnce())->method('assignProducts')->with([$sku]);
        $this->sharedCatalogAssignment->expects($this->atLeastOnce())->method('getAssignCategoryIdsByProducts')
            ->with([$product])->willReturn($categoryIds);
        $this->sharedCatalogAssignment->expects($this->atLeastOnce())->method('getAssignProductsSku')
            ->with([$product])->willReturn([$sku]);
        $storage->expects($this->atLeastOnce())->method('assignCategories')->with($categoryIds);
        $this->productTierPriceLoader->expects($this->atLeastOnce())->method('populateProductTierPrices')
            ->with([$product], 1, $storage);

        $this->sharedCatalogMassAssignment->assign($collection, $storage, 1, true);
    }

    /**
     * Unit test for assign() for products unassign action.
     *
     * @return void
     */
    public function testAssignProductsUnassignAction()
    {
        $sku = 'sku';
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collection = $this->getMockBuilder(AbstractCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->atLeastOnce())->method('setPageSize')->willReturnSelf();
        $collection->expects($this->atLeastOnce())->method('getLastPageNumber')->willReturn(1);
        $collection->expects($this->atLeastOnce())->method('setCurPage')->with(1)->willReturnSelf();
        $collection->expects($this->atLeastOnce())->method('getItems')->willReturn([$product]);
        $this->sharedCatalogAssignment->expects($this->atLeastOnce())->method('getAssignProductsSku')
            ->with([$product])->willReturn([$sku]);
        $storage->expects($this->atLeastOnce())->method('unassignProducts')->with([$sku]);
        $this->productTierPriceLoader->expects($this->atLeastOnce())->method('populateProductTierPrices')
            ->with([$product], 1, $storage);

        $this->sharedCatalogMassAssignment->assign($collection, $storage, 1, false);
    }
}
