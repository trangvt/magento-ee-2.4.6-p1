<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\SharedCatalogLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for class SharedCatalogLocator.
 */
class SharedCatalogLocatorTest extends TestCase
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
     * @var SharedCatalogLocator
     */
    private $model;

    /**
     * {@inheritdoc}
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
            SharedCatalogLocator::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
            ]
        );
    }

    /**
     * Test getSharedCatalogByCustomerGroup method.
     *
     * @return void
     */
    public function testGetSharedCatalogByCustomerGroup()
    {
        $customerGroupId = 1;
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('addFilter')
            ->with(SharedCatalogInterface::CUSTOMER_GROUP_ID, $customerGroupId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn(1);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$sharedCatalog]);

        $this->assertSame($sharedCatalog, $this->model->getSharedCatalogByCustomerGroup($customerGroupId));
    }

    /**
     * Test getSharedCatalogByCustomerGroup method throws exception.
     *
     * @return void
     */
    public function testGetSharedCatalogByCustomerGroupWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such shared catalog entity');
        $customerGroupId = 1;
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('addFilter')
            ->with(SharedCatalogInterface::CUSTOMER_GROUP_ID, $customerGroupId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn(0);

        $this->assertSame($sharedCatalog, $this->model->getSharedCatalogByCustomerGroup($customerGroupId));
    }
}
