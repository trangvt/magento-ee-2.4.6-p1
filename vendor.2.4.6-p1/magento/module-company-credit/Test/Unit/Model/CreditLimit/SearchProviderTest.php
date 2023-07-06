<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\CreditLimit;

use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\CreditLimit;
use Magento\CompanyCredit\Model\CreditLimit\SearchProvider;
use Magento\CompanyCredit\Model\ResourceModel\CreditLimit\Collection;
use Magento\CompanyCredit\Model\ResourceModel\CreditLimit\CollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
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
 * Unit tests for \Magento\CompanyCredit\Model\CreditLimit\SearchProvider model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchProviderTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $creditLimitCollectionFactory;

    /**
     * @var SearchResultsInterfaceFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var JoinProcessorInterface|MockObject
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var SearchProvider
     */
    private $searchProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimitCollectionFactory =
            $this->getMockBuilder(CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();

        $this->searchResultsFactory = $this->getMockBuilder(SearchResultsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->extensionAttributesJoinProcessor =
            $this->getMockBuilder(JoinProcessorInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->searchProvider = $objectManager->getObject(
            SearchProvider::class,
            [
                'creditLimitCollectionFactory'     => $this->creditLimitCollectionFactory,
                'searchResultsFactory'             => $this->searchResultsFactory,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
            ]
        );
    }

    /**
     * Test for method getList.
     *
     * @return void
     */
    public function testGetList()
    {
        $filterField = CreditLimitInterface::COMPANY_ID;
        $filterValue = 3;
        $conditionType = 'neq';
        $collectionSize = 1;
        $sortOrderField = CreditLimitInterface::BALANCE;
        $sortOrderDirection = 'ASC';
        $currentPage = 2;
        $pageSize = 15;

        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $searchResults = $this->getMockBuilder(SearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchResultsFactory->expects($this->once())->method('create')->willReturn($searchResults);
        $searchResults->expects($this->once())->method('setSearchCriteria')->with($searchCriteria)->willReturnSelf();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditLimitCollectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $this->extensionAttributesJoinProcessor->expects($this->once())->method('process')->with($collection);
        $filterGroup = $this->getMockBuilder(FilterGroup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->once())->method('getFilterGroups')->willReturn([$filterGroup]);
        $filter = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterGroup->expects($this->once())->method('getFilters')->willReturn([$filter]);
        $filter->expects($this->once())->method('getConditionType')->willReturn($conditionType);
        $filter->expects($this->once())->method('getField')->willReturn($filterField);
        $filter->expects($this->once())->method('getValue')->willReturn($filterValue);
        $collection->expects($this->once())->method('addFieldToFilter')
            ->with($filterField, [$conditionType => $filterValue])->willReturnSelf();
        $collection->expects($this->once())->method('getSize')->willReturn($collectionSize);
        $searchResults->expects($this->once())->method('setTotalCount')->with($collectionSize)->willReturnSelf();
        $sortOrder = $this->getMockBuilder(SortOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteria->expects($this->once())->method('getSortOrders')->willReturn([$sortOrder]);
        $sortOrder->expects($this->once())->method('getField')->willReturn($sortOrderField);
        $sortOrder->expects($this->once())->method('getDirection')->willReturn($sortOrderDirection);
        $collection->expects($this->once())->method('addOrder')
            ->with($sortOrderField, $sortOrderDirection)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getCurrentPage')->willReturn($currentPage);
        $collection->expects($this->once())->method('setCurPage')->with($currentPage)->willReturnSelf();
        $searchCriteria->expects($this->once())->method('getPageSize')->willReturn($pageSize);
        $collection->expects($this->once())->method('setPageSize')->with($pageSize)->willReturnSelf();
        $creditLimit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())->method('getItems')->willReturn([$creditLimit]);
        $searchResults->expects($this->once())->method('setItems')->with([$creditLimit])->willReturnSelf();
        $this->assertEquals($searchResults, $this->searchProvider->getList($searchCriteria));
    }
}
