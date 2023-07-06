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
use Magento\SharedCatalog\Model\Management;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Management unit test.
 */
class ManagementTest extends TestCase
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
     * @var SharedCatalogFactory|MockObject
     */
    private $sharedCatalogFactory;

    /**
     * @var Management
     */
    private $management;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->sharedCatalogRepository =
            $this->getMockForAbstractClass(SharedCatalogRepositoryInterface::class);
        $this->sharedCatalogFactory = $this->createMock(SharedCatalogFactory::class);
        $objectManager = new ObjectManager($this);
        $this->management = $objectManager->getObject(
            Management::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'sharedCatalogFactory' => $this->sharedCatalogFactory
            ]
        );
    }

    /**
     * Test getPublicCatalog.
     *
     * @return void
     */
    public function testGetPublicCatalog()
    {
        $this->prepareMocksGetPublicCatalog();

        $this->assertInstanceOf(
            SharedCatalogInterface::class,
            $this->management->getPublicCatalog()
        );
    }

    /**
     * Test getPublicCatalog with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetPublicCatalogWithNoSuchEntityException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->prepareMocksGetPublicCatalogWithNoSuchEntityException();

        $this->management->getPublicCatalog();
    }

    /**
     * Test isPublicCatalogExists.
     *
     * @return void
     */
    public function testIsPublicCatalogExist()
    {
        $this->prepareMocksGetPublicCatalog();

        $this->assertTrue($this->management->isPublicCatalogExist());
    }

    /**
     * Test isPublicCatalogExists with NoSuchEntityException.
     *
     * @return void
     */
    public function testIsPublicCatalogExistWithNoSuchEntityException()
    {
        $this->prepareMocksGetPublicCatalogWithNoSuchEntityException();

        $this->assertNotTrue($this->management->isPublicCatalogExist());
    }

    /**
     * Prepare mocks getPublicCatalog.
     *
     * @return void
     */
    private function prepareMocksGetPublicCatalog()
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $sharedCatalog = $this->getMockForAbstractClass(SharedCatalogInterface::class);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(2);
        $searchResults->expects($this->once())->method('getItems')->willReturn([$sharedCatalog, $sharedCatalog]);
        $this->sharedCatalogRepository->expects($this->once())->method('getList')->with($searchCriteria)
            ->willReturn($searchResults);
    }

    /**
     * Prepare mocks getPublicCatalog with NoSuchEntityException.
     *
     * @return void
     */
    private function prepareMocksGetPublicCatalogWithNoSuchEntityException()
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(0);
        $this->sharedCatalogRepository->expects($this->once())->method('getList')->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->never())->method('getItems');
    }
}
