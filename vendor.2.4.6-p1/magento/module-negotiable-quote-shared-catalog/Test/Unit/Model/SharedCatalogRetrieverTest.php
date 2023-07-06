<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuoteSharedCatalog\Model\SharedCatalogRetriever;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SharedCatalogRetrieverTest model.
 */
class SharedCatalogRetrieverTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SharedCatalogRetriever
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogRepository = $this->getMockBuilder(
            SharedCatalogRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            SharedCatalogRetriever::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
            ]
        );
    }

    /**
     * Test sharedCatalogExists method.
     *
     * @param int $totalCount
     * @param bool $expectedResult
     * @return void
     * @dataProvider sharedCatalogExistsDataProvider
     */
    public function testSharedCatalogExists($totalCount, $expectedResult)
    {
        $customerGroupId = 6;
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(SharedCatalogInterface::CUSTOMER_GROUP_ID, $customerGroupId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn($totalCount);

        $this->assertEquals($expectedResult, $this->model->isSharedCatalogPresent($customerGroupId));
    }

    /**
     * Data provider fot sharedCatalogExists method.
     *
     * @return array
     */
    public function sharedCatalogExistsDataProvider()
    {
        return [
            [1, true],
            [0, false]
        ];
    }

    /**
     * Test getPublicCatalog method.
     *
     * @return void
     */
    public function testGetPublicCatalog()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(SharedCatalogInterface::TYPE, SharedCatalogInterface::TYPE_PUBLIC)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);
        $searchResults->expects($this->once())->method('getItems')->willReturn([$sharedCatalog]);

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->model->getPublicCatalog()
        );
    }

    /**
     * Test getPublicCatalog method with exception.
     *
     * @return void
     */
    public function testGetPublicCatalogWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such public catalog entity');
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(SharedCatalogInterface::TYPE, SharedCatalogInterface::TYPE_PUBLIC)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(0);

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->model->getPublicCatalog()
        );
    }
}
