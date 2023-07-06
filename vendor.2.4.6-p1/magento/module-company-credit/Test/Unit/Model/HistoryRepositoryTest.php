<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Model\CreditLimit;
use Magento\CompanyCredit\Model\Email\Sender;
use Magento\CompanyCredit\Model\HistoryFactory;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\HistoryRepository;
use Magento\CompanyCredit\Model\ResourceModel\History;
use Magento\CompanyCredit\Model\ResourceModel\History\Collection;
use Magento\CompanyCredit\Model\ResourceModel\History\CollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryRepositoryTest extends TestCase
{
    /**
     * @var HistoryFactory|MockObject
     */
    private $historyFactory;

    /**
     * @var History|MockObject
     */
    private $historyResource;

    /**
     * @var CollectionFactory|MockObject
     */
    private $historyCollectionFactory;

    /**
     * @var \Magento\CompanyCredit\Model\HistorySearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var Sender|MockObject
     */
    private $emailSender;

    /**
     * @var HistoryRepository
     */
    private $historyRepository;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->historyFactory = $this->createPartialMock(
            HistoryFactory::class,
            ['create']
        );
        $this->historyResource = $this->createMock(
            History::class
        );
        $this->historyCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->searchResultsFactory = $this->createPartialMock(
            SearchResultsInterfaceFactory::class,
            ['create']
        );
        $this->emailSender = $this->createMock(
            Sender::class
        );

        $objectManager = new ObjectManager($this);
        $this->historyRepository = $objectManager->getObject(
            HistoryRepository::class,
            [
                'historyFactory' => $this->historyFactory,
                'historyResource' => $this->historyResource,
                'historyCollectionFactory' => $this->historyCollectionFactory,
                'searchResultsFactory' => $this->searchResultsFactory,
                'emailSender' => $this->emailSender,
            ]
        );
    }

    /**
     * Test for method save.
     *
     * @return void
     */
    public function testSave()
    {
        $history = $this->createMock(\Magento\CompanyCredit\Model\History::class);
        $this->historyResource->expects($this->once())->method('save')->with($history)->willReturnSelf();
        $this->emailSender->expects($this->once())
            ->method('sendCompanyCreditChangedNotificationEmail')
            ->with($history)
            ->willReturnSelf();
        $this->assertEquals($history, $this->historyRepository->save($history));
    }

    /**
     * Test for method save with exception.
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not save history');
        $history = $this->createMock(\Magento\CompanyCredit\Model\History::class);
        $this->historyResource->expects($this->once())->method('save')->with($history)
            ->willThrowException(new \Exception('Exception message'));
        $this->historyRepository->save($history);
    }

    /**
     * Test for method get.
     *
     * @return void
     */
    public function testGet()
    {
        $historyId = 1;
        $history = $this->createMock(\Magento\CompanyCredit\Model\History::class);
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->historyResource->expects($this->once())->method('load')->with($history, $historyId)->willReturnSelf();
        $history->expects($this->once())->method('getId')->willReturn($historyId);
        $this->assertEquals($history, $this->historyRepository->get($historyId));
    }

    /**
     * Test for method get with exception.
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $historyId = 1;
        $history = $this->createMock(\Magento\CompanyCredit\Model\History::class);
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->historyResource->expects($this->once())->method('load')->with($history, $historyId)->willReturnSelf();
        $history->expects($this->once())->method('getId')->willReturn(null);
        $this->assertEquals($history, $this->historyRepository->get($historyId));
    }

    /**
     * Test for method delete.
     *
     * @return void
     */
    public function testDelete()
    {
        $history = $this->createMock(\Magento\CompanyCredit\Model\History::class);
        $history->expects($this->once())->method('getId')->willReturn(1);
        $this->historyResource->expects($this->once())->method('delete')->with($history)->willReturnSelf();
        $this->assertTrue($this->historyRepository->delete($history));
    }

    /**
     * Test for method delete with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('Cannot delete history with id 1');
        $history = $this->createMock(\Magento\CompanyCredit\Model\History::class);
        $history->expects($this->exactly(2))->method('getId')->willReturn(1);
        $this->historyResource->expects($this->once())->method('delete')->with($history)
            ->willThrowException(new \Exception('Exception message'));
        $this->historyRepository->delete($history);
    }

    /**
     * Test for method getList.
     *
     * @return void
     */
    public function testGetList()
    {
        $filterField = HistoryInterface::TYPE;
        $filterValue = HistoryInterface::TYPE_REIMBURSED;
        $conditionType = 'neq';
        $collectionSize = 1;
        $sortOrderField = HistoryInterface::DATETIME;
        $sortOrderDirection = 'DESC';
        $currentPage = 2;
        $pageSize = 15;

        $searchCriteria = $this->createMock(
            SearchCriteriaInterface::class
        );
        $searchResults = $this->getMockForAbstractClass(SearchResultInterface::class);
        $this->searchResultsFactory->expects($this->once())->method('create')->willReturn($searchResults);
        $searchResults->expects($this->once())->method('setSearchCriteria')->with($searchCriteria)->willReturnSelf();
        $collection = $this->createMock(
            Collection::class
        );
        $this->historyCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $filterGroup = $this->createMock(FilterGroup::class);
        $searchCriteria->expects($this->once())->method('getFilterGroups')->willReturn([$filterGroup]);
        $filter = $this->createMock(Filter::class);
        $filterGroup->expects($this->once())->method('getFilters')->willReturn([$filter]);
        $filter->expects($this->once())->method('getConditionType')->willReturn($conditionType);
        $filter->expects($this->once())->method('getField')->willReturn($filterField);
        $filter->expects($this->once())->method('getValue')->willReturn($filterValue);
        $collection->expects($this->once())->method('addFieldToFilter')
            ->with($filterField, [$conditionType => $filterValue])->willReturnSelf();
        $collection->expects($this->once())->method('getSize')->willReturn($collectionSize);
        $searchResults->expects($this->once())->method('setTotalCount')->with($collectionSize)->willReturnSelf();
        $sortOrder = $this->createMock(SortOrder::class);
        $searchCriteria->expects($this->once())->method('getSortOrders')->willReturn([$sortOrder]);
        $sortOrder->expects($this->once())->method('getField')->willReturn($sortOrderField);
        $sortOrder->expects($this->once())->method('getDirection')->willReturn($sortOrderDirection);
        $collection->expects($this->once())->method('addOrder')
            ->with($sortOrderField, $sortOrderDirection)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getCurrentPage')->willReturn($currentPage);
        $collection->expects($this->once())->method('setCurPage')->with($currentPage)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getPageSize')->willReturn($pageSize);
        $collection->expects($this->once())->method('setPageSize')->with($pageSize)->willReturnSelf();
        $creditLimit = $this->createMock(CreditLimit::class);
        $collection->expects($this->once())->method('getItems')->willReturn([$creditLimit]);
        $searchResults->expects($this->once())->method('setItems')->with([$creditLimit])->willReturnSelf();
        $this->assertEquals($searchResults, $this->historyRepository->getList($searchCriteria));
    }
}
