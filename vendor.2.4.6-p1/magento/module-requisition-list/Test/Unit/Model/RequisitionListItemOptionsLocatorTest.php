<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionListItem\Option;
use Magento\RequisitionList\Model\RequisitionListItemOptionsFactory;
use Magento\RequisitionList\Model\RequisitionListItemOptionsLocator;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for RequisitionListItemOptionsLocator model.
 */
class RequisitionListItemOptionsLocatorTest extends TestCase
{
    /**
     * @var RequisitionListItemOptionsFactory|MockObject
     */
    private $requisitionListOptionsItemFactory;

    /**
     * @var RequisitionListItemProduct|MockObject
     */
    private $requisitionListItemProduct;

    /**
     * @var OptionsManagement|MockObject
     */
    private $optionsManagement;

    /**
     * @var RequisitionListItemOptionsLocator
     */
    private $requisitionListItemOptionsLocator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionListOptionsItemFactory = $this
            ->getMockBuilder(RequisitionListItemOptionsFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemProduct = $this
            ->getMockBuilder(RequisitionListItemProduct::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsManagement = $this->getMockBuilder(OptionsManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->requisitionListItemOptionsLocator = $objectManager->getObject(
            RequisitionListItemOptionsLocator::class,
            [
                'requisitionListOptionsItemFactory' => $this->requisitionListOptionsItemFactory,
                'requisitionListItemProduct' => $this->requisitionListItemProduct,
                'optionsManagement' => $this->optionsManagement,
            ]
        );
    }

    /**
     * Test for getOptions method.
     *
     * @return void
     */
    public function testGetOptions()
    {
        $itemId = 1;
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getId')->willReturn($itemId);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->once())
            ->method('getProduct')->with($item)->willReturn($product);
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsManagement->expects($this->once())->method('getOptions')->with($item)->willReturn([$option]);
        $optionItem = $this->getMockBuilder(ItemInterface::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListOptionsItemFactory->expects($this->once())->method('create')->willReturn($optionItem);
        $optionItem->expects($this->atLeastOnce())->method('setData')
            ->withConsecutive(['product', $product], ['options', [$option]])->willReturnSelf();
        $this->assertEquals($optionItem, $this->requisitionListItemOptionsLocator->getOptions($item));
    }
}
