<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Block\Requisition\View\Items;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Block\Requisition\View\Items\Grid;
use Magento\RequisitionList\Model\RequisitionList\ItemSelector;
use Magento\RequisitionList\Model\RequisitionListItem\Validation;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\RequisitionList\Block\Requisition\View\Items\Grid.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GridTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Validation|MockObject
     */
    private $validation;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ItemSelector|MockObject
     */
    private $itemSelector;

    /**
     * @var Grid
     */
    private $grid;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsite'])
            ->getMockForAbstractClass();
        $this->validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemSelector = $this->getMockBuilder(ItemSelector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->grid = $objectManager->getObject(
            Grid::class,
            [
                '_request' => $this->request,
                'storeManager' => $this->storeManager,
                'validation' => $this->validation,
                'itemSelector' => $this->itemSelector
            ]
        );
    }

    /**
     * Test for getRequisitionListItems method.
     *
     * @return void
     */
    public function testGetRequisitionListItems(): void
    {
        $requisitionListId = 1;
        $websiteId = 1;

        $this->request->expects($this->once())
            ->method('getParam')->with('requisition_id')->willReturn($requisitionListId);
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($websiteMock);
        $websiteMock->expects($this->atLeastOnce())->method('getId')->willReturn($websiteId);
        $this->itemSelector->expects($this->atLeastOnce())->method('selectAllItemsFromRequisitionList')
            ->with($requisitionListId, $websiteId)->willReturn([$requisitionListItem]);
        $this->validation->expects($this->once())->method('validate')->with($requisitionListItem)->willReturn([]);
        $this->assertEquals([$requisitionListItem], $this->grid->getRequisitionListItems());
        $this->assertEquals(0, $this->grid->getItemErrorCount());
    }

    /**
     * Test for getRequisitionListItems method with empty requisition list id.
     *
     * @return void
     */
    public function testGetRequisitionListItemsWithEmptyRequisitionListId(): void
    {
        $this->request->expects($this->once())->method('getParam')->with('requisition_id')->willReturn(null);
        $this->assertNull($this->grid->getRequisitionListItems());
    }

    /**
     * Test for getRequisitionListItems method with validation errors.
     *
     * @return void
     */
    public function testGetRequisitionListItemsWithValidationErrors(): void
    {
        $requisitionListId = 1;
        $websiteId = 1;

        $validationError = 'Item validation error';
        $this->request->expects($this->once())
            ->method('getParam')->with('requisition_id')->willReturn($requisitionListId);
        $requisitionListItems = [
            $this->getMockBuilder(RequisitionListItemInterface::class)
                ->onlyMethods(['getSku'])
                ->addMethods(['setProduct', 'setNoProduct', 'setItemError', 'getItemError'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass(),
            $this->getMockBuilder(RequisitionListItemInterface::class)
                ->onlyMethods(['getSku'])
                ->addMethods(['setProduct', 'setNoProduct', 'setItemError', 'getItemError'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass(),
            $this->getMockBuilder(RequisitionListItemInterface::class)
                ->onlyMethods(['getSku'])
                ->addMethods(['setProduct', 'setNoProduct', 'setItemError', 'getItemError'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass()
        ];
        $this->itemSelector->expects($this->atLeastOnce())->method('selectAllItemsFromRequisitionList')
            ->with($requisitionListId, $websiteId)->willReturn($requisitionListItems);
        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($websiteMock);
        $websiteMock->expects($this->atLeastOnce())->method('getId')->willReturn($websiteId);
        $this->validation
            ->method('validate')
            ->withConsecutive(
                [$requisitionListItems[0]],
                [$requisitionListItems[1]],
                [$requisitionListItems[2]]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [$validationError],
                $this->throwException(new NoSuchEntityException())
            );
        $requisitionListItems[0]->expects($this->atLeastOnce())->method('getId')->willReturn(10);
        $requisitionListItems[1]->expects($this->atLeastOnce())->method('getId')->willReturn(11);
        $requisitionListItems[2]->expects($this->atLeastOnce())->method('getId')->willReturn(12);
        $this->assertSame(
            [2 => $requisitionListItems[2], 1 => $requisitionListItems[1], 0 => $requisitionListItems[0]],
            $this->grid->getRequisitionListItems()
        );
        $this->assertEquals(2, $this->grid->getItemErrorCount());
        $this->assertEquals([], $this->grid->getItemErrors($requisitionListItems[0]));
        $this->assertEquals([$validationError], $this->grid->getItemErrors($requisitionListItems[1]));
        $this->assertEquals(
            [__('The SKU was not found in the catalog.')],
            $this->grid->getItemErrors($requisitionListItems[2])
        );
    }
}
