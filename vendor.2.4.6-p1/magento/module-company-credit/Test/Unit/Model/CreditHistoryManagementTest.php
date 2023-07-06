<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Model\CreditHistoryManagement;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\HistoryRepositoryInterface;
use Magento\CompanyCredit\Model\ResourceModel\History;
use Magento\CompanyCredit\Model\ResourceModel\History\Collection;
use Magento\CompanyCredit\Model\ResourceModel\History\CollectionFactory as HistoryCollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreditHistoryManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditHistoryManagementTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CreditHistoryManagement
     */
    private $creditHistoryManagement;

    /**
     * @var History|MockObject
     */
    private $historyResourceMock;

    /**
     * @var HistoryCollectionFactory|MockObject
     */
    private $historyCollectionFactoryMock;

    /**
     * @var SearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactoryMock;

    /**
     * @var HistoryRepositoryInterface|MockObject
     */
    private $historyRepositoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->historyResourceMock = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyCollectionFactoryMock = $this->getMockBuilder(HistoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->searchResultsFactoryMock = $this
            ->getMockBuilder(SearchResultsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->historyRepositoryMock = $this
            ->getMockBuilder(HistoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->creditHistoryManagement = $this->objectManagerHelper->getObject(
            CreditHistoryManagement::class,
            [
                'historyResource' => $this->historyResourceMock,
                'historyCollectionFactory' => $this->historyCollectionFactoryMock,
                'searchResultsFactory' => $this->searchResultsFactoryMock,
                'historyRepository' => $this->historyRepositoryMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * Test for update method.
     *
     * @return void
     */
    public function testUpdate()
    {
        $historyId = 1;
        $purchaseOrder = 'PO-001';
        $comment = 'History comment';
        $historyComments = ['system' => 'System comment'];
        $history = $this->getMockBuilder(\Magento\CompanyCredit\Model\History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyRepositoryMock->expects($this->once())->method('get')->with($historyId)->willReturn($history);
        $history->expects($this->once())->method('getType')
            ->willReturn(HistoryInterface::TYPE_REIMBURSED);
        $history->expects($this->atLeastOnce())->method('setPurchaseOrder')->with($purchaseOrder)->willReturnSelf();
        $history->expects($this->atLeastOnce())->method('getComment')->willReturn(json_encode($historyComments));
        $this->serializerMock->expects($this->once())
            ->method('unserialize')->with(json_encode($historyComments))->willReturn($historyComments);
        $this->serializerMock->expects($this->once())->method('serialize')
            ->with($historyComments + ['custom' => $comment])
            ->willReturn(json_encode($historyComments + ['custom' => $comment]));
        $history->expects($this->once())
            ->method('setComment')->with(json_encode($historyComments + ['custom' => $comment]))->willReturnSelf();
        $this->historyResourceMock->expects($this->once())->method('save')->with($history)->willReturn($history);
        $this->assertTrue($this->creditHistoryManagement->update($historyId, $purchaseOrder, $comment));
    }

    /**
     * Test for update method with save exception.
     *
     * @return void
     */
    public function testUpdateWithSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Could not update history');
        $historyId = 1;
        $history = $this->getMockBuilder(\Magento\CompanyCredit\Model\History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyRepositoryMock->expects($this->once())->method('get')->with($historyId)->willReturn($history);
        $history->expects($this->once())->method('getType')
            ->willReturn(HistoryInterface::TYPE_REIMBURSED);
        $phrase = new Phrase(__('Exception'));
        $this->historyResourceMock->expects($this->once())
            ->method('save')->with($history)->willThrowException(
                new CouldNotSaveException($phrase)
            );
        $this->creditHistoryManagement->update($historyId);
    }

    /**
     * Test for update method with wrong type.
     *
     * @return void
     */
    public function testUpdateWithWrongType()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Cannot process the request. Please check the operation type and try again.');
        $historyId = 1;
        $history = $this->getMockBuilder(\Magento\CompanyCredit\Model\History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyRepositoryMock->expects($this->once())->method('get')->with($historyId)->willReturn($history);
        $history->expects($this->once())->method('getType')
            ->willReturn(HistoryInterface::TYPE_ALLOCATED);
        $this->creditHistoryManagement->update($historyId);
    }

    /**
     * Test for getList method.
     *
     * @return void
     */
    public function testGetList()
    {
        $filterField = 'entity_id';
        $filterValue = 1;
        $sortField = 'datetime';
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchResultsFactoryMock->expects($this->once())->method('create')->willReturn($searchResults);
        $searchResults->expects($this->once())->method('setSearchCriteria')->with($searchCriteria)->willReturnSelf();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyCollectionFactoryMock->expects($this->once())->method('create')->willReturn($collection);
        $filterGroup = $this->getMockBuilder(FilterGroup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->once())->method('getFilterGroups')->willReturn([$filterGroup]);
        $filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterGroup->expects($this->once())->method('getFilters')->willReturn([$filter]);
        $filter->expects($this->once())->method('getConditionType')->willReturn(null);
        $filter->expects($this->once())->method('getField')->willReturn($filterField);
        $filter->expects($this->once())->method('getValue')->willReturn($filterValue);
        $collection->expects($this->once())
            ->method('addFieldToFilter')->with($filterField, ['eq' => $filterValue])->willReturnSelf();
        $collection->expects($this->once())->method('getSize')->willReturn(1);
        $searchResults->expects($this->once())->method('setTotalCount')->with(1)->willReturnSelf();
        $sortOrder = $this->getMockBuilder(SortOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->once())->method('getSortOrders')->willReturn([$sortOrder]);
        $sortOrder->expects($this->once())->method('getField')->willReturn($sortField);
        $sortOrder->expects($this->once())->method('getDirection')->willReturn(null);
        $collection->expects($this->once())->method('addOrder')->with($sortField)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getCurrentPage')->willReturn(2);
        $collection->expects($this->once())->method('setCurPage')->with(2)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getPageSize')->willReturn(20);
        $collection->expects($this->once())->method('setPageSize')->with(20)->willReturnSelf();
        $history = $this->getMockBuilder(HistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collection->expects($this->once())->method('getItems')->willReturn([$history]);
        $searchResults->expects($this->once())->method('setItems')->with([$history])->willReturnSelf();
        $this->assertEquals($searchResults, $this->creditHistoryManagement->getList($searchCriteria));
    }
}
