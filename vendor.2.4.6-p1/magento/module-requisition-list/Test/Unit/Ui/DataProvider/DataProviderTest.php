<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Ui\DataProvider;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Ui\DataProvider\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProviderTest extends TestCase
{
    /**
     * @var Reporting|MockObject
     */
    private $reporting;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var UserContextInterface|MockObject
     */
    private $customerContext;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var SearchCriteria|MockObject
     */
    private $searchCriteria;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->reporting = $this->createMock(Reporting::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);
        $this->customerContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->requisitionListRepository =
            $this->getMockForAbstractClass(RequisitionListRepositoryInterface::class);
        $this->searchCriteria = $this->createMock(SearchCriteria::class);
        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            DataProvider::class,
            [
                'searchCriteria' => $this->searchCriteria,
                'reporting' => $this->reporting,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'request' => $this->request,
                'filterBuilder' => $this->filterBuilder,
                'customerContext' => $this->customerContext,
                'requisitionListRepository' => $this->requisitionListRepository,
                'meta' => [],
                'data' => []
            ]
        );
    }

    /**
     * Test getData
     */
    public function testGetData()
    {
        $searchResultItem =
            $this->getMockBuilder(ExtensibleDataInterface::class)
                ->addMethods(['getData'])
                ->getMockForAbstractClass();
        $searchResultItem->expects($this->any())->method('getData')->willReturn(['a' => 'value 1', 'b' => 'value 2']);
        $searchResult = $this->getSearchResult();
        $searchResult->expects($this->any())->method('getTotalCount')->willReturn(1);
        $searchResult->expects($this->any())->method('getItems')->willReturn([$searchResultItem]);
        $items = [
            'totalRecords' => 1,
            'items' => [
                [
                    'a' => 'value 1',
                    'b' => 'value 2'
                ]
            ]
        ];

        $this->assertEquals($items, $this->dataProvider->getData());
    }

    /**
     * Test getSearchResult
     */
    public function testGetSearchResult()
    {
        $this->getSearchResult();

        $this->assertInstanceOf(
            SearchResultsInterface::class,
            $this->dataProvider->getSearchResult()
        );
    }

    /**
     * Prepare getSearchResult mocks
     *
     * @return SearchResultsInterface|MockObject
     */
    private function getSearchResult()
    {
        $this->customerContext->expects($this->any())->method('getUserId')->willReturn(1);
        $filter = $this->createMock(Filter::class);
        $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturn($filter);
        $this->searchCriteria->expects($this->any())->method('setRequestName')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())->method('create')->willReturn($this->searchCriteria);
        $searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->requisitionListRepository->expects($this->any())->method('getList')->willReturn($searchResult);

        return $searchResult;
    }
}
