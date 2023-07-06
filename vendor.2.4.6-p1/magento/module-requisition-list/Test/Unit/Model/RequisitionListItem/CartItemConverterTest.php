<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem;

use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\Data\ProductOptionInterfaceFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Quote\Model\Quote\ProductOption;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\RequisitionList\Model\RequisitionListItem\CartItemConverter;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CartItemConverter.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartItemConverterTest extends TestCase
{
    /**
     * @var CartItemInterfaceFactory|MockObject
     */
    private $cartItemFactoryMock;

    /**
     * @var ProductOptionInterfaceFactory|MockObject
     */
    private $productOptionFactoryMock;

    /**
     * @var CartItemOptionsProcessor|MockObject
     */
    private $cartItemProcessorMock;

    /**
     * @var RequisitionListItemProduct|MockObject
     */
    private $requisitionListItemProduct;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var CartItemConverter
     */
    private $cartItemConverter;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartItemFactoryMock = $this->getMockBuilder(CartItemInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productOptionFactoryMock =
            $this->getMockBuilder(ProductOptionInterfaceFactory::class)
                ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->cartItemProcessorMock =
            $this->getMockBuilder(CartItemOptionsProcessor::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->requisitionListItemProduct = $this
            ->getMockBuilder(RequisitionListItemProduct::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->cartItemConverter = $objectManagerHelper->getObject(
            CartItemConverter::class,
            [
                'cartItemFactory' => $this->cartItemFactoryMock,
                'productOptionFactory' => $this->productOptionFactoryMock,
                'cartItemProcessor' => $this->cartItemProcessorMock,
                'requisitionListItemProduct' => $this->requisitionListItemProduct,
                'serializer' => $this->serializer,
            ]
        );
    }

    /**
     * Test convert().
     *
     * @return void
     */
    public function testConvert()
    {
        $itemMock = $this->getMockBuilder(RequisitionListItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $buyRequestMock = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->atLeastOnce())
            ->method('getCustomOptions')
            ->willReturn([$buyRequestMock]);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($productMock);
        $cartItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartItemMock->expects($this->atLeastOnce())
            ->method('getOptionByCode')
            ->willReturn($buyRequestMock);
        $this->cartItemFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($cartItemMock);
        $productOptionMock = $this->getMockBuilder(ProductOption::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productOptionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($productOptionMock);
        $this->serializer->expects($this->atLeastOnce())->method('serialize')->willReturn('serialized');
        $cartItem = $this->cartItemConverter->convert($itemMock);

        $this->assertInstanceOf(CartItemInterface::class, $cartItem);
    }
}
