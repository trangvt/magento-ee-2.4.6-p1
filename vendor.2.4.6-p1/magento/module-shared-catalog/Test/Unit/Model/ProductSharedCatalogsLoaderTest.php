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
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\ProductItemSearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SearchResultsInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Model\ProductSharedCatalogsLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model ProductSharedCatalogsLoader.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductSharedCatalogsLoaderTest extends TestCase
{
    /**
     * @var ProductItemRepositoryInterface|MockObject
     */
    private $linkRepository;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductSharedCatalogsLoader
     */
    private $productSharedCatalogsLoader;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->linkRepository = $this->getMockBuilder(ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogRepository = $this->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productSharedCatalogsLoader = (new ObjectManager($this))->getObject(
            ProductSharedCatalogsLoader::class,
            [
                'linkRepository' => $this->linkRepository,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder
            ]
        );
    }

    /**
     * Test for getAssignedSharedCatalogs().
     *
     * @return void
     */
    public function testGetAssignedSharedCatalogs(): void
    {
        $sku = 'sku';
        $customerGroupIdFirst = 1;
        $customerGroupIdSecond = 2;
        $sharedCatalogIdFirst = 3;
        $sharedCatalogIdSecond = 4;

        $linkSearchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $linkFirst = $this->getMockBuilder(ProductItemInterface::class)
            ->onlyMethods(['getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $linkFirst->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupIdFirst);

        $linkSecond = $this->getMockBuilder(ProductItemInterface::class)
            ->onlyMethods(['getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $linkSecond->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupIdSecond);

        $linkItemSearchResults = $this->getMockBuilder(ProductItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $linkItems = [$linkFirst, $linkSecond];
        $linkItemSearchResults->expects($this->once())->method('getItems')->willReturn($linkItems);

        $this->linkRepository->expects($this->once())->method('getList')->with($linkSearchCriteria)
            ->willReturn($linkItemSearchResults);

        $sharedCatalogSearchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerGroupTable = SharedCatalogInterface::CUSTOMER_GROUP_ID;
        $this->searchCriteriaBuilder
            ->method('addFilter')
            ->withConsecutive(
                [ProductItemInterface::SKU, $sku],
                [$customerGroupTable, [$customerGroupIdFirst, $customerGroupIdSecond], 'in']
            )
            ->willReturnOnConsecutiveCalls($this->searchCriteriaBuilder, $this->searchCriteriaBuilder);
        $this->searchCriteriaBuilder
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $linkSearchCriteria,
                $sharedCatalogSearchCriteria
            );

        $sharedCatalogFirst = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogFirst->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogIdFirst);

        $sharedCatalogSecond = $this->getMockBuilder(SharedCatalogInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogSecond->expects($this->atLeastOnce())->method('getId')->willReturn($sharedCatalogIdSecond);

        $sharedCatalogSearchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalogs = [$sharedCatalogFirst, $sharedCatalogSecond];
        $sharedCatalogSearchResults->expects($this->any())->method('getItems')->willReturn($sharedCatalogs);
        $this->sharedCatalogRepository->expects($this->once())->method('getList')->with($sharedCatalogSearchCriteria)
            ->willReturn($sharedCatalogSearchResults);

        $expectedResult = [
            $sharedCatalogIdFirst => $sharedCatalogFirst,
            $sharedCatalogIdSecond => $sharedCatalogSecond,
        ];
        $this->assertEquals($expectedResult, $this->productSharedCatalogsLoader->getAssignedSharedCatalogs($sku));
    }
}
