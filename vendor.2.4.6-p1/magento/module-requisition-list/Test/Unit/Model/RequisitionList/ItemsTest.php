<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionList;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Model\RequisitionList\Items;
use Magento\RequisitionList\Model\ResourceModel\RequisitionList\Item\Collection;
use Magento\RequisitionList\Model\ResourceModel\RequisitionList\Item\CollectionFactory as ItemCollectionFactory;
use Magento\RequisitionList\Model\ResourceModel\RequisitionListItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemsTest extends TestCase
{
    /**
     * @var Items
     */
    private $requisitionListItemRepository;

    /**
     * @var RequisitionListItemInterfaceFactory|MockObject
     */
    private $requisitionListItemFactory;

    /**
     * @var RequisitionListItem|MockObject
     */
    private $requisitionListItemResource;

    /**
     * @var JoinProcessorInterface|MockObject
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var SearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var ItemCollectionFactory|MockObject
     */
    private $requisitionListItemCollectionFactory;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionListItem|MockObject
     */
    private $requisitionListItem;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessorMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->requisitionListItem = $this->createMock(\Magento\RequisitionList\Model\RequisitionListItem::class);

        $this->requisitionListItemFactory = $this->createPartialMock(
            RequisitionListItemInterfaceFactory::class,
            ['create']
        );
        $this->requisitionListItemFactory
            ->expects($this->any())->method('create')->willReturn($this->requisitionListItem);
        $this->requisitionListItem
            ->expects($this->any())->method('load')->willReturnSelf();

        $this->requisitionListItemResource =
            $this->createMock(RequisitionListItem::class);

        $this->extensionAttributesJoinProcessor =
            $this->getMockForAbstractClass(JoinProcessorInterface::class);

        $this->searchResultsFactory =
            $this->createPartialMock(SearchResultsInterfaceFactory::class, ['create']);
        $searchResult = new SearchResults();
        $this->searchResultsFactory->expects($this->any())->method('create')->willReturn($searchResult);

        $this->requisitionListItemCollectionFactory = $this->createPartialMock(
            \Magento\RequisitionList\Model\ResourceModel\RequisitionList\Item\CollectionFactory::class,
            ['create']
        );

        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->requisitionListItemRepository = $objectManagerHelper->getObject(
            Items::class,
            [
                'requisitionListItemFactory' => $this->requisitionListItemFactory,
                'requisitionListItemResource' => $this->requisitionListItemResource,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'searchResultsFactory' => $this->searchResultsFactory,
                'collectionFactory' => $this->requisitionListItemCollectionFactory,
                'collectionProcessor' => $this->collectionProcessorMock,
            ]
        );
    }

    /**
     * Test for method save
     */
    public function testSave()
    {
        $this->requisitionListItem->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->requisitionListItemRepository->save($this->requisitionListItem);
    }

    /**
     * Test for method save
     */
    public function testSaveWithSomeError()
    {
        $this->requisitionListItem->expects($this->any())->method('getId')->willReturn(1);
        $this->requisitionListItemResource
            ->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception());
        try {
            $this->requisitionListItemRepository->save($this->requisitionListItem);
        } catch (\Exception $e) {
            $this->assertEquals(__('Could not save Requisition List'), $e->getMessage());
        }
    }

    /**
     * Test for method get
     */
    public function testGet()
    {
        $this->requisitionListItem->expects($this->any())->method('getId')->willReturn(1);
        $this->assertEquals($this->requisitionListItem, $this->requisitionListItemRepository->get(1));
    }

    public function testGetIfRequisitionListIsNotFound()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with id = 1');
        $this->requisitionListItem->expects($this->any())->method('getId')->willReturn(0);
        $this->assertNull($this->requisitionListItemRepository->get(1));
    }

    /**
     * Test for method delete
     */
    public function testDelete()
    {
        $this->requisitionListItem->expects($this->once())->method('getId')->willReturn(2);
        $this->requisitionListItemResource
            ->expects($this->once())
            ->method('delete')
            ->with($this->requisitionListItem)
            ->willReturn(true);
        $this->assertTrue($this->requisitionListItemRepository->delete($this->requisitionListItem));
    }

    /**
     * Test for method delete
     */
    public function testDeleteWithError()
    {
        $requisitionListItemId = 2;
        $exception = new StateException(
            new Phrase('Cannot delete Requisition List with id %1', [$requisitionListItemId])
        );
        $this->requisitionListItem->expects($this->any())->method('getId')->willReturn($requisitionListItemId);
        $this->requisitionListItemResource
            ->expects($this->any())
            ->method('delete')
            ->willThrowException(new \Exception());

        try {
            $this->requisitionListItemRepository->delete($this->requisitionListItem);
        } catch (\Exception $e) {
            $this->assertEquals(
                $e->getMessage(),
                $exception->getMessage()
            );
        }
    }

    /**
     * Test for method deleteById
     */
    public function testDeleteById()
    {
        $requisitionListItemId = 3;
        $this->requisitionListItem->expects($this->any())->method('getId')->willReturn($requisitionListItemId);
        $this->requisitionListItem
            ->expects($this->once())
            ->method('load')
            ->with($requisitionListItemId)
            ->willReturn($this->requisitionListItem);
        $this->requisitionListItemResource
            ->expects($this->once())
            ->method('delete')
            ->with($this->requisitionListItem)
            ->willReturn(true);
        $this->assertTrue($this->requisitionListItemRepository->deleteById($requisitionListItemId));
    }

    /**
     * Test for method getList
     * @dataProvider getParamsForModel
     *
     * @param $count
     * @param $expectedResult
     */
    public function testGetList($count, $expectedResult)
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);

        $collection =
            $this->createMock(Collection::class);
        $this->requisitionListItemCollectionFactory->expects($this->any())
            ->method('create')->willReturn($collection);
        $collection->expects($this->any())->method('getItems')->willReturn([]);
        $collection->expects($this->any())->method('getSize')->willReturn($count);

        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $collection);

        $result = $this->requisitionListItemRepository->getList($searchCriteria);
        $this->assertEquals($expectedResult, $result->getTotalCount());
    }

    /**
     * Data provider for method testGetList
     * @return array
     */
    public function getParamsForModel()
    {
        return [
            [0, 0],
            [1, 1]
        ];
    }
}
