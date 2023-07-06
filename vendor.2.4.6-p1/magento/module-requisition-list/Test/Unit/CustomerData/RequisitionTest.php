<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\CustomerData;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\CustomerData\Requisition;
use Magento\RequisitionList\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequisitionTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Requisition|MockObject
     */
    private $requisition;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepositoryMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var Config|MockObject
     */
    private $moduleConfigMock;

    /**
     * @var SortOrderBuilder|MockObject
     */
    private $sortOrderBuilderMock;

    /**
     * @var SearchResultsInterface|MockObject
     */
    private $searchResultsMock;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionListRepositoryMock = $this->getMockBuilder(
            RequisitionListRepositoryInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->userContextMock = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->moduleConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sortOrderBuilderMock = $this->getMockBuilder(SortOrderBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilter')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addSortOrder')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $sortOrder = $this->getMockBuilder(SortOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sortOrderBuilderMock->expects($this->any())->method('setField')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->any())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->any())->method('create')->willReturn($sortOrder);

        $this->searchResultsMock = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListRepositoryMock->expects($this->any())
            ->method('getList')
            ->willReturn($this->searchResultsMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->requisition = $this->objectManagerHelper->getObject(
            Requisition::class,
            [
                'requisitionListRepository' => $this->requisitionListRepositoryMock,
                'userContext' => $this->userContextMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'moduleConfig' => $this->moduleConfigMock,
                'sortOrderBuilder' => $this->sortOrderBuilderMock
            ]
        );
    }

    /**
     * @param int $customerId
     * @param array $items
     * @param int $maxListCount
     * @param mixed $expectedCustomerData
     * @return void
     *
     * @dataProvider getSectionDataProvider
     */
    public function testGetSectionData($customerId, array $items, $maxListCount, $expectedCustomerData)
    {
        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->moduleConfigMock->expects($this->any())
            ->method('getMaxCountRequisitionList')
            ->willReturn($maxListCount);

        $this->searchResultsMock->expects($this->any())
            ->method('getItems')
            ->willReturn($items);

        $this->assertEquals($expectedCustomerData, $this->requisition->getSectionData());
    }

    /**
     * Data provider for getSectionData
     *
     * @return array
     */
    public function getSectionDataProvider()
    {
        return [
            [
                1,
                [
                    $this->buildListMock(1, 'name1'),
                    $this->buildListMock(2, 'name2'),
                    $this->buildListMock(3, 'name3'),
                ],
                5,
                [
                    'items' => [
                        [
                            'id' => 1,
                            'name' => 'name1'
                        ],
                        [
                            'id' => 2,
                            'name' => 'name2'
                        ],
                        [
                            'id' => 3,
                            'name' => 'name3'
                        ],
                    ],
                    'max_allowed_requisition_lists' => 5,
                    'is_enabled' => false
                ]
            ],
            [
                null,
                [],
                2,
                [
                    'items' => [],
                    'max_allowed_requisition_lists' => 2,
                    'is_enabled' => false
                ]
            ],
            [
                1,
                [],
                3,
                [
                    'items' => [],
                    'max_allowed_requisition_lists' => 3,
                    'is_enabled' => false
                ]
            ]
        ];
    }

    /**
     * Build list mock
     *
     * @param int|null $id
     * @param string|null $name
     * @return RequisitionListInterface|MockObject
     */
    private function buildListMock($id = null, $name = null)
    {
        $listMock = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $listMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $listMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        return $listMock;
    }
}
