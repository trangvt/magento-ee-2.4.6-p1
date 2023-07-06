<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardRequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCardRequisitionList\Model\AddToCartProcessor;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
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
            ->setMethods(['getTypeId', 'getAllowOpenAmount'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeId')->willReturn('giftcard');
        $product->expects($this->atLeastOnce())->method('getAllowOpenAmount')->willReturn(false);
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();
        $cartItem->expects($this->atLeastOnce())->method('getData')->with('product')->willReturn($product);
        $productOptions = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getGiftcardAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $productOptions->expects($this->never())->method('getGiftcardAmount');
        $this->cartItemOptionProcessor->expects($this->atLeastOnce())->method('getBuyRequest')
            ->willReturn($productOptions);
        $cart = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProduct'])
            ->getMockForAbstractClass();
        $cart->expects($this->atLeastOnce())->method('addProduct')->with($product, $productOptions);
        $this->addToCartProcessor->execute($cart, $cartItem);
    }

    /**
     * Test for execute() for gift cards with allowed open amount.
     *
     * @return void
     */
    public function testExecuteWithAllowedOpenAmount()
    {
        $giftCardAmount = 10;
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId', 'getAllowOpenAmount'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeId')->willReturn('giftcard');
        $product->expects($this->atLeastOnce())->method('getAllowOpenAmount')->willReturn(true);
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();
        $cartItem->expects($this->atLeastOnce())->method('getData')->with('product')->willReturn($product);
        $productOptions = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftcardAmount', 'setGiftcardAmount', 'setCustomGiftcardAmount'])
            ->getMock();
        $productOptions->expects($this->atLeastOnce())->method('getGiftcardAmount')->willReturn($giftCardAmount);
        $productOptions->expects($this->atLeastOnce())->method('setCustomGiftcardAmount')->with($giftCardAmount)
            ->willReturnSelf();
        $productOptions->expects($this->atLeastOnce())->method('setGiftcardAmount')->with(null)->willReturnSelf();
        $this->cartItemOptionProcessor->expects($this->atLeastOnce())->method('getBuyRequest')
            ->willReturn($productOptions);
        $cart = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addProduct'])
            ->getMockForAbstractClass();
        $cart->expects($this->atLeastOnce())->method('addProduct')->with($product, $productOptions);
        $this->addToCartProcessor->execute($cart, $cartItem);
    }
}
