<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Model\RequisitionList\Items;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Locator.
 */
class LocatorTest extends TestCase
{
    /**
     * @var Items|MockObject
     */
    private $requisitionListItemRepository;

    /**
     * @var RequisitionListItemInterfaceFactory|MockObject
     */
    private $requisitionListItemFactory;

    /**
     * @var Locator
     */
    private $locator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionListItemRepository = $this
            ->getMockBuilder(Items::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemFactory = $this
            ->getMockBuilder(RequisitionListItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->locator = $objectManagerHelper->getObject(
            Locator::class,
            [
                'requisitionListItemRepository' => $this->requisitionListItemRepository,
                'requisitionListItemFactory' => $this->requisitionListItemFactory,
            ]
        );
    }

    /**
     * Test for getItem().
     *
     * @return void
     */
    public function testGetItem()
    {
        $itemId = 1;
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemRepository->expects($this->atLeastOnce())->method('get')->with($itemId)
            ->willReturn($requisitionListItem);
        $this->requisitionListItemFactory->expects($this->never())->method('create');

        $this->assertInstanceOf(
            RequisitionListItemInterface::class,
            $this->locator->getItem($itemId)
        );
    }

    /**
     * Test for getItem() with empty item id.
     *
     * @return void
     */
    public function testGetItemWithEmptyItemId()
    {
        $itemId = null;
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($requisitionListItem);
        $this->requisitionListItemRepository->expects($this->never())->method('get')->with($itemId);

        $this->assertInstanceOf(
            RequisitionListItemInterface::class,
            $this->locator->getItem($itemId)
        );
    }
}
