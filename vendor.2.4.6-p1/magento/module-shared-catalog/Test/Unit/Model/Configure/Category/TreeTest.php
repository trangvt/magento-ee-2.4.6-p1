<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Configure\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\ResourceModel\CategoryTree;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\SharedCatalog\Model\Configure\Category\Tree.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TreeTest extends TestCase
{
    /**
     * @var Tree|MockObject
     */
    private $treeResource;

    /**
     * @var Wizard|MockObject
     */
    private $wizardStorage;

    /**
     * @var CategoryTree|MockObject
     */
    private $categoryTree;

    /**
     * @var \Magento\SharedCatalog\Model\Configure\Category\Tree
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->treeResource = $this->getMockBuilder(Tree::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryTree = $this->getMockBuilder(CategoryTree::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\SharedCatalog\Model\Configure\Category\Tree::class,
            [
                'treeResource' => $this->treeResource,
                'categoryTree' => $this->categoryTree
            ]
        );
    }

    /**
     * Test getCategoryRootNode method.
     *
     * @param int $storeId
     * @param int $categoryId
     * @param InvokedCount $counter
     * @return void
     * @dataProvider getCategoryRootNodeDataProvider
     */
    public function testGetCategoryRootNode($storeId, $categoryId, $counter)
    {
        $productSkus = ['sku_1', 'sku_2', 'sku_3'];
        $categoriesIds = [1, 2, 3];
        $level = 0;
        $nodeId = 2;
        $storeGroup = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $tree = $this->getMockBuilder(\Magento\Framework\Data\Tree::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCollectionData', 'getNodeById'])
            ->getMock();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $node = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->treeResource->expects($this->once())->method('load')->willReturn($tree);
        $storeGroup->expects($this->any())->method('getId')->willReturn($storeId);
        $storeGroup->expects($counter)->method('getRootCategoryId')->willReturn($categoryId);
        $this->wizardStorage->expects($this->once())->method('getAssignedProductSkus')->willReturn($productSkus);
        $this->wizardStorage->expects($this->once())->method('getAssignedCategoriesIds')->willReturn($categoriesIds);
        $this->categoryTree
            ->expects($this->once())
            ->method('getCategoryCollection')
            ->with($categoryId, $productSkus)
            ->willReturn($collection);
        $tree->expects($this->once())->method('addCollectionData')->with($collection)->willReturnSelf();
        $tree->expects($this->once())->method('getNodeById')->with($categoryId)->willReturn($node);
        $node->expects($this->exactly(4))
            ->method('getData')
            ->withConsecutive(['level'], ['entity_id'])
            ->willReturnOnConsecutiveCalls($level, $nodeId);
        $node->expects($this->exactly(6))
            ->method('setData')
            ->withConsecutive(['is_checked', true], ['is_active', 1], ['is_checked', false])
            ->willReturnSelf();
        $node->expects($this->exactly(2))->method('getIsActive')->willReturn(2);
        $node->expects($this->exactly(2))
            ->method('getChildren')
            ->willReturnOnConsecutiveCalls(
                new \ArrayIterator([$node]),
                new \ArrayIterator([])
            );

        $this->assertSame($node, $this->model->getCategoryRootNode($this->wizardStorage));
    }

    /**
     * Data provider for getCategoryRootNode method.
     *
     * @return array
     */
    public function getCategoryRootNodeDataProvider()
    {
        return [
            [Store::DEFAULT_STORE_ID, Category::TREE_ROOT_ID, $this->never()],
            [2, Category::TREE_ROOT_ID, $this->never()]
        ];
    }
}
