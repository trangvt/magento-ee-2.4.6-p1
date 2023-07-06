<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionList;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionList\Items;
use Magento\RequisitionList\Model\RequisitionList\ItemsLoadHandler;
use PHPUnit\Framework\TestCase;

class ItemsLoadHandlerTest extends TestCase
{
    /**
     * @var Items
     */
    private $requisitionListItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ItemsLoadHandler
     */
    private $itemsLoadHandler;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->requisitionListItemRepository =
            $this->createMock(Items::class);
        $this->searchCriteriaBuilder =
            $this->createMock(SearchCriteriaBuilder::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->itemsLoadHandler = $objectManagerHelper->getObject(
            ItemsLoadHandler::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'requisitionListItemRepository' => $this->requisitionListItemRepository
            ]
        );
    }

    /**
     * Test for method load
     */
    public function testLoad()
    {
        $rlId = 1;
        $requisitionList = $this->getMockForAbstractClass(
            RequisitionListInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getId', 'setItems']
        );
        $requisitionList->expects($this->once())->method('getId')->willReturn($rlId);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(RequisitionListItemInterface::REQUISITION_LIST_ID)
            ->willReturnSelf();
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->requisitionListItemRepository->expects($this->once())
            ->method('getList')
            ->willReturn($searchResults);
        $item =
            $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $items = [$item];
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn($items);
        $requisitionList->expects($this->once())->method('setItems')->with($items)->willReturnSelf();
        $this->assertEquals($requisitionList, $this->itemsLoadHandler->load($requisitionList));
    }
}
