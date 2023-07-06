<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionList;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionList\Items as ItemsRepository;
use Magento\RequisitionList\Model\RequisitionList\ItemSelector;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Requisition List ItemsSelector model.
 */
class ItemSelectorTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ItemSelector
     */
    private $itemSelector;

    /**
     * @var ItemsRepository|MockObject
     */
    private $requisitionListItemRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var RequisitionListItemProduct|MockObject
     */
    private $requisitionListItemProductMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requisitionListItemRepositoryMock = $this->getMockBuilder(ItemsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemProductMock = $this->getMockBuilder(RequisitionListItemProduct::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->itemSelector = $this->objectManagerHelper->getObject(
            ItemSelector::class,
            [
                'requisitionListItemRepository' => $this->requisitionListItemRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'requisitionListItemProduct' => $this->requisitionListItemProductMock
            ]
        );
    }

    /**
     * Test for selectAllItemsFromRequisitionList() method.
     *
     * @return void
     */
    public function testSelectAllItemsFromRequisitionList()
    {
        $requisitionListId = 1;
        $websiteId = 1;
        $productSku = 'SKU01';

        $this->searchCriteriaBuilderMock->expects($this->once())->method('addFilter')
            ->with(RequisitionListItemInterface::REQUISITION_LIST_ID, $requisitionListId)
            ->wilLReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemRepositoryMock->expects($this->once())->method('getList')->willReturn($searchResults);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $items = [$requisitionListItem];
        $searchResults->expects($this->once())->method('getItems')->willReturn($items);
        $this->requisitionListItemProductMock->expects($this->once())
            ->method('extract')->with([$requisitionListItem], $websiteId, true)->willReturn([$productSku => $product]);
        $requisitionListItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->requisitionListItemProductMock->expects($this->once())->method('setProduct')
            ->with($requisitionListItem, $product)->willReturnSelf();
        $this->requisitionListItemProductMock->expects($this->once())->method('setIsProductAttached')
            ->with($requisitionListItem, true)->willReturnSelf();

        $this->assertEquals(
            [$requisitionListItem],
            $this->itemSelector->selectAllItemsFromRequisitionList($requisitionListId, $websiteId)
        );
    }

    /**
     * Test for selectItemsFromRequisitionList() method.
     *
     * @return void
     */
    public function testSelectItemsFromRequisitionList()
    {
        $requisitionListId = 1;
        $websiteId = 1;
        $productSku = 'SKU01';
        $itemIds = [1];

        $this->searchCriteriaBuilderMock->expects($this->exactly(2))->method('addFilter')
            ->withConsecutive(
                [RequisitionListItemInterface::REQUISITION_LIST_ID, $requisitionListId],
                [RequisitionListItemInterface::REQUISITION_LIST_ITEM_ID, $itemIds, 'in']
            )
            ->wilLReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemRepositoryMock->expects($this->once())->method('getList')->willReturn($searchResults);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $items = [$requisitionListItem];
        $searchResults->expects($this->once())->method('getItems')->willReturn($items);
        $this->requisitionListItemProductMock->expects($this->once())
            ->method('extract')->with([$requisitionListItem], $websiteId, false)->willReturn([$productSku => $product]);
        $requisitionListItem->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $this->requisitionListItemProductMock->expects($this->once())->method('setProduct')
            ->with($requisitionListItem, $product)->willReturnSelf();
        $this->requisitionListItemProductMock->expects($this->once())->method('setIsProductAttached')
            ->with($requisitionListItem, true)->willReturnSelf();

        $this->assertEquals(
            [$requisitionListItem],
            $this->itemSelector->selectItemsFromRequisitionList($requisitionListId, $itemIds, $websiteId)
        );
    }
}
