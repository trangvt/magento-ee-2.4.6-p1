<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Magento\RequisitionList\Model\AddToCartProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AddToCartProcessor.
 */
class AddToCartProcessorTest extends TestCase
{
    /**
     * @var CartItemOptionsProcessor|MockObject
     */
    private $cartItemOptionProcessor;

    /**
     * @var AddToCartProcessor
     */
    private $addToCartProcessor;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartItemOptionProcessor = $this
            ->getMockBuilder(CartItemOptionsProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->addToCartProcessor = $objectManagerHelper->getObject(
            AddToCartProcessor::class,
            [
                'cartItemOptionProcessor' => $this->cartItemOptionProcessor,
            ]
        );
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeId')->willReturn('simple');
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();
        $cartItem->expects($this->atLeastOnce())->method('getData')->with('product')->willReturn($product);
        $buyRequest = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartItemOptionProcessor->expects($this->atLeastOnce())->method('getBuyRequest')->willReturn($buyRequest);
        $cart = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProduct'])
            ->getMockForAbstractClass();
        $cart->expects($this->atLeastOnce())->method('addProduct')->with($product, $buyRequest);
        $this->addToCartProcessor->execute($cart, $cartItem);
    }
}
