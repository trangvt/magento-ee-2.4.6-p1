<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\DB\Helper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\QuickOrder\Model\CatalogPermissions\Permissions;
use Magento\QuickOrder\Model\ResourceModel\Product\Suggest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterfaceFactory;

/**
 * Unit tests for Suggest resource model.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SuggestTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Suggest
     */
    private $suggest;

    /**
     * @var Permissions|MockObject
     */
    private $permissionsMock;

    /**
     * @var Helper|MockObject
     */
    private $dbHelperMock;


    /**
     * @var SearchResultApplierInterfaceFactory|MockObject
     */
    private $searchResultApplierFactory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permissionsMock = $this->getMockBuilder(Permissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dbHelperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchResultApplierFactory = $this->getMockBuilder(
            SearchResultApplierInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->suggest = $this->objectManagerHelper->getObject(
            Suggest::class,
            [
                'permissions' => $this->permissionsMock,
                'dbHelper' => $this->dbHelperMock,
                'searchResultApplierInterfaceFactory' => $this->searchResultApplierFactory
            ]
        );
    }

    /**
     * Test for prepareProductCollection().
     *
     * @return void
     */
    public function testPrepareProductCollection()
    {
        $query = 'test';
        /** @var MockObject|Collection $productCollectionMock */
        $productCollectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods([
                'addAttributeToSelect',
                'getSelect',
                'setOrder',
                'addAttributeToFilter'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock->expects($this->once())->method('addAttributeToSelect')->willReturnSelf();
        /** @var MockObject|SearchResultInterface $searchResultMock */
        $searchResultMock = $this->getMockBuilder(SearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchApplierMock = $this->getMockBuilder(
            SearchResultApplierInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $searchResultMock->expects($this->atLeastOnce())->method('getSearchCriteria')->willReturn($searchCriteriaMock);
        $this->permissionsMock->expects($this->once())->method('applyPermissionsToProductCollection')
            ->with($productCollectionMock)
            ->willReturnSelf();
        $this->dbHelperMock->expects($this->once())->method('escapeLikeValue')
            ->with($query, ['position' => 'any'])->willReturn($query);
        $productCollectionMock->expects($this->exactly(2))->method('addAttributeToFilter')
            ->withConsecutive(
                [
                    [
                        ['attribute' => ProductInterface::SKU, 'like' => $query],
                        ['attribute' => ProductInterface::NAME, 'like' => $query]
                    ],
                ]
            )
            ->willReturnSelf();
        $this->searchResultApplierFactory->expects($this->once())->method('create')->with(
            [
                'collection' => $productCollectionMock,
                'searchResult' => $searchResultMock,
                'size' => null,
                'currentPage' => null
            ]
        )->willReturn($searchApplierMock);

        $this->suggest->prepareProductCollection($productCollectionMock, $searchResultMock, 10, $query);
    }
}
