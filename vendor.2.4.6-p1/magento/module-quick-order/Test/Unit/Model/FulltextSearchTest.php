<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\QuickOrder\Model\FulltextSearch;
use Magento\Search\Api\SearchInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for Quick Order FulltextSearch model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FulltextSearchTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var FulltextSearch
     */
    private $fulltextSearch;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilderMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var SearchInterface|MockObject
     */
    private $searchMock;

    /**
     * @var SearchResultFactory|MockObject
     */
    private $searchResultFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var string
     */
    private $query = 'test';

    /**
     * @var SearchCriteria|MockObject
     */
    private $searchCriteriaMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchMock = $this->getMockBuilder(SearchInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchResultFactoryMock = $this->getMockBuilder(SearchResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->fulltextSearch = $this->objectManagerHelper->getObject(
            FulltextSearch::class,
            [
                'filterBuilder' => $this->filterBuilderMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'search' => $this->searchMock,
                'searchResultFactory' => $this->searchResultFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test for search().
     *
     * @return void
     * @throws LocalizedException
     */
    public function testSearch()
    {
        $this->prepareSearchMocks();

        $searchResultsMock = $this->getMockBuilder(SearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchMock->expects($this->once())->method('search')->with($this->searchCriteriaMock)
            ->willReturn($searchResultsMock);
        $searchResultMock = $this->getMockBuilder(DocumentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResultsMock->expects($this->once())->method('getItems')->willReturn([$searchResultMock]);

        $this->assertSame([$searchResultMock], $this->fulltextSearch->search($this->query, 0)->getItems());
    }

    /**
     * Test for search() method when EmptyRequestDataException is thrown.
     *
     * @throws LocalizedException
     * @return void
     */
    public function testSearchWithEmptyRequestDataException()
    {
        $this->prepareSearchMocks();
        $exception = new EmptyRequestDataException('Exception message');
        $this->searchMock->expects($this->once())->method('search')->with($this->searchCriteriaMock)
            ->willThrowException($exception);
        $searchResultsMock = $this->getMockBuilder(SearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchResultFactoryMock->expects($this->once())->method('create')->willReturn($searchResultsMock);
        $searchResultsMock->expects($this->once())->method('setItems')->with([])->willReturnSelf();
        $searchResultsMock->expects($this->once())->method('getItems')->willReturn([]);

        $this->assertSame([], $this->fulltextSearch->search($this->query, 0)->getItems());
    }

    /**
     * Test for search() method when NonExistingRequestNameException is thrown.
     *
     * @return void
     */
    public function testSearchWithNonExistingRequestNameException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('An error occurred. For details, see the error log.');
        $this->prepareSearchMocks();
        $exception = new NonExistingRequestNameException('Exception message');
        $this->searchMock->expects($this->once())->method('search')->with($this->searchCriteriaMock)
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())->method('error');

        $this->fulltextSearch->search($this->query, 0);
    }

    /**
     * Prepare mock objects for search test.
     *
     * @return void
     */
    private function prepareSearchMocks()
    {
        $this->filterBuilderMock->expects($this->once())->method('setField')->with('search_term');
        $this->filterBuilderMock->expects($this->once())->method('setValue')->with($this->query);
        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilderMock->expects($this->once())->method('create')->willReturn($filterMock);
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addFilter')->with($filterMock);
        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->searchCriteriaMock->expects($this->once())->method('setRequestName')->willReturnSelf();
        $this->searchCriteriaMock->expects($this->once())->method('setPageSize')->with(500)->willReturnSelf();
    }
}
