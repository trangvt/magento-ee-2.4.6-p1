<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem\OrderItem;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\RequisitionList\Model\RequisitionListItem\OrderItem\Converter;
use Magento\Sales\Api\Data\OrderItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Converter.
 */
class ConverterTest extends TestCase
{
    /**
     * @var Builder|MockObject
     */
    private $optionsBuilder;

    /**
     * @var RequisitionListItemInterfaceFactory|MockObject
     */
    private $requisitionListItemFactory;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->optionsBuilder = $this
            ->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemFactory = $this
            ->getMockBuilder(RequisitionListItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->converter = $objectManagerHelper->getObject(
            Converter::class,
            [
                'optionsBuilder' => $this->optionsBuilder,
                'requisitionListItemFactory' => $this->requisitionListItemFactory,
            ]
        );
    }

    /**
     * Test for convert() method.
     *
     * @return void
     */
    public function testConvert()
    {
        $sku = 'sku';
        $productOptions = ['info_buyRequest' => ['options', 'product' => null]];
        $orderItem = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductOptions'])
            ->getMockForAbstractClass();
        $orderItem->expects($this->atLeastOnce())->method('getProductOptions')->willReturn($productOptions);
        $orderItem->expects($this->atLeastOnce())->method('getQtyOrdered')->willReturn(1);
        $this->optionsBuilder->expects($this->atLeastOnce())->method('build')
            ->with($productOptions['info_buyRequest'], 0)->willReturn([]);
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionListItem->expects($this->atLeastOnce())->method('setQty')->willReturnSelf();
        $requisitionListItem->expects($this->atLeastOnce())->method('setOptions')->willReturnSelf();
        $requisitionListItem->expects($this->atLeastOnce())->method('setSku')->willReturnSelf();
        $this->requisitionListItemFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($requisitionListItem);

        $this->assertEquals($requisitionListItem, $this->converter->convert($orderItem, $sku));
    }
}
