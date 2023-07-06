<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListInterfaceFactory;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\Config;
use Magento\RequisitionList\Model\RequisitionList\Items;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Magento\RequisitionList\Model\ResourceModel\RequisitionList;
use Magento\RequisitionList\Model\ResourceModel\RequisitionList\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequisitionListRepositoryTest extends TestCase
{
    /**
     * @var RequisitionListInterfaceFactory|MockObject
     */
    protected $requisitionListFactory;

    /**
     * @var RequisitionList|MockObject
     */
    protected $requisitionListResource;

    /**
     * @var JoinProcessorInterface|MockObject
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var SearchResultsInterfaceFactory|MockObject
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactory;

    /**
     * @var Items|MockObject
     */
    protected $requisitionListItemRepository;

    /**
     * @var Config|MockObject
     */
    protected $moduleConfig;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    protected $searchCriteriaBuilder;

    /**
     * @var RequisitionListRepository|MockObject
     */
    protected $requisitionListRepository;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionList|MockObject
     */
    private $requisitionList;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionList|MockObject
     */
    private $collection;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessorMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionList = $this->createMock(\Magento\RequisitionList\Model\RequisitionList::class);

        $this->requisitionListFactory = $this->createPartialMock(
            RequisitionListInterfaceFactory::class,
            ['create']
        );
        $this->requisitionListFactory
            ->expects($this->any())->method('create')->willReturn($this->requisitionList);
        $this->requisitionList
            ->expects($this->any())->method('load')->willReturnSelf();

        $this->requisitionListResource =
            $this->createMock(RequisitionList::class);
        $this->extensionAttributesJoinProcessor =
            $this->getMockForAbstractClass(JoinProcessorInterface::class);
        $this->searchResultsFactory =
            $this->createPartialMock(SearchResultsInterfaceFactory::class, ['create']);
        $this->collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->requisitionListItemRepository =
            $this->createMock(Items::class);
        $this->moduleConfig = $this->createMock(Config::class);
        $this->searchCriteriaBuilder =
            $this->createMock(SearchCriteriaBuilder::class);
        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->requisitionListRepository = $objectManager->getObject(
            RequisitionListRepository::class,
            [
                'requisitionListFactory' => $this->requisitionListFactory,
                'requisitionListResource' => $this->requisitionListResource,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'searchResultsFactory' => $this->searchResultsFactory,
                'collectionFactory' => $this->collectionFactory,
                'requisitionListItemRepository' => $this->requisitionListItemRepository,
                'moduleConfig' => $this->moduleConfig,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'collectionProcessor' => $this->collectionProcessorMock,
            ]
        );
    }

    /**
     * Test save() method
     *
     * @return void
     */
    public function testSave()
    {
        $this->mockCollection();
        $this->collection->expects($this->any())->method('getSize')->willReturnOnConsecutiveCalls(1, 1, 1, 0);
        $requisitionListItem = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $this->collection->expects($this->any())->method('getItems')->willReturn([$requisitionListItem]);
        $this->requisitionList->expects($this->any())
            ->method('getItems')
            ->willReturn([$requisitionListItem]);
        $this->requisitionList->expects($this->any())->method('getId')->willReturn(123);

        $this->requisitionListRepository->save($this->requisitionList, true);
    }

    /**
     * Test save() method with items deletion
     *
     * @return void
     */
    public function testSaveWithItemsDeletion()
    {
        $this->mockCollection();
        $this->collection->expects($this->any())->method('getSize')->willReturnOnConsecutiveCalls(1, 1, 1, 0);
        $requisitionListItem = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $this->requisitionList->expects($this->any())
            ->method('getItems')
            ->willReturnOnConsecutiveCalls([], [$requisitionListItem]);
        $this->requisitionListItemRepository->expects($this->once())
            ->method('get')
            ->willReturn($requisitionListItem);
        $this->requisitionListItemRepository->expects($this->once())->method('delete');
        $this->requisitionList->expects($this->any())->method('getId')->willReturn(123);
        $this->collection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collection->expects($this->any())->method('getItems')->willReturn([$requisitionListItem]);

        $this->requisitionListRepository->save($this->requisitionList, true);
    }

    /**
     * Test save() method with exception
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->mockCollection();
        $this->requisitionList->expects($this->any())->method('getId')->willReturn(null);

        $this->requisitionListRepository->save($this->requisitionList, true);
    }

    /**
     * Test save() method with exception from repository
     *
     * @return void
     */
    public function testSaveWithExceptionFromRepository()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->mockCollection();
        $requisitionListItem = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $this->requisitionList->expects($this->any())->method('getId')->willReturn(123);
        $this->requisitionList->expects($this->any())
            ->method('getItems')
            ->willReturn([$requisitionListItem]);
        $this->requisitionListItemRepository->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception());

        $this->requisitionListRepository->save($this->requisitionList, true);
    }

    /**
     * Mock collection for save
     *
     * @return void
     */
    private function mockCollection()
    {
        $this->collection = $this->getMockForAbstractClass(
            AbstractDb::class,
            [],
            '',
            false,
            false,
            true,
            ['getSize', 'getItems', 'addFieldToFilter', 'load']
        );
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collection);
        $this->collection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collection->expects($this->any())->method('load')->willReturnSelf();
    }

    /**
     * Test get() method
     *
     * @return void
     */
    public function testGet()
    {
        $requisitionListId = 1;
        $this->requisitionList->expects($this->once())
            ->method('getId')
            ->willReturn($requisitionListId);
        $this->requisitionList->expects($this->once())
            ->method('load')
            ->with($requisitionListId);
        $this->requisitionListFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->requisitionList);
        $this->requisitionListRepository->get($requisitionListId);
    }

    /**
     * Test get() method with exception
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $requisitionListId = 1;
        $this->requisitionList->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->requisitionList->expects($this->once())
            ->method('load')
            ->with($requisitionListId);
        $this->requisitionListFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->requisitionList);
        $this->requisitionListRepository->get($requisitionListId);
    }

    /**
     * Test delete() method
     *
     * @return void
     */
    public function testDelete()
    {
        $requisitionList = $this->createPartialMock(\Magento\RequisitionList\Model\RequisitionList::class, ['getId']);
        $this->requisitionListRepository->delete($requisitionList);
    }

    /**
     * Test delete() method with exception
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $requisitionListId = 1;
        $requisitionList = $this->createPartialMock(\Magento\RequisitionList\Model\RequisitionList::class, ['getId']);
        $this->requisitionListResource->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception());
        $requisitionList->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($requisitionListId);
        $this->requisitionListRepository->delete($requisitionList);
    }

    /**
     * Test for method deleteById
     *
     * @return void
     */
    public function testDeleteById()
    {
        $requisitionListId = 3;
        $this->requisitionList->expects($this->any())->method('getId')->willReturn($requisitionListId);
        $this->requisitionList
            ->expects($this->once())
            ->method('load')
            ->with($requisitionListId)
            ->willReturn($this->requisitionList);
        $this->requisitionListResource
            ->expects($this->once())
            ->method('delete')
            ->with($this->requisitionList)
            ->willReturn(true);
        $this->assertTrue($this->requisitionListRepository->deleteById($requisitionListId));
    }
}
