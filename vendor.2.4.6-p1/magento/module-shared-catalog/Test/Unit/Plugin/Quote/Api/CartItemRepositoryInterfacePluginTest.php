<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Plugin\Quote\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessorFactory;
use Magento\SharedCatalog\Plugin\Quote\Api\CartItemRepositoryInterfacePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartItemRepositoryInterfacePluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CartItemRepositoryInterfacePlugin|MockObject
     */
    private $cartItemRepositoryInterfacePlugin;

    /**
     * @var CartItemRepositoryInterface|MockObject
     */
    private $cartItemRepository;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var Quote|MockObject
     */
    private $cart;

    /**
     * @var CartItemOptionsProcessorFactory|MockObject
     */
    private $cartItemOptionsProcessorFactoryMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartItemOptionsProcessorFactoryMock =
            $this->createPartialMock(
                CartItemOptionsProcessorFactory::class,
                ['create']
            );
        $this->cartItemRepository = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cart = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->cartItemRepositoryInterfacePlugin = $this->objectManagerHelper->getObject(
            CartItemRepositoryInterfacePlugin::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'cartItemOptionsProcessorFactory' => $this->cartItemOptionsProcessorFactoryMock
            ]
        );
    }

    /**
     * Test for method aroundGetList
     */
    public function testAroundGetList()
    {
        $quoteItem = $this->createMock(Item::class);
        $this->cart->method('getAllVisibleItems')->willReturn([$quoteItem]);
        $this->quoteRepositoryMock->method('get')->willReturn($this->cart);
        $closure = function () {
            return;
        };
        $processor = $this->getMockBuilder(CartItemOptionsProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processor->method('addProductOptions')->willReturn($quoteItem);
        $processor->method('applyCustomOptions')->willReturn($quoteItem);
        $this->cartItemOptionsProcessorFactoryMock->method('create')->willReturn($processor);
        $result =
            $this->cartItemRepositoryInterfacePlugin->aroundGetList($this->cartItemRepository, $closure, 1);
        $this->assertEquals([$quoteItem], $result);
    }

    /**
     * Test for method aroundGetList
     */
    public function testAroundGetListQuoteNotFound()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $e = new NoSuchEntityException();
        $this->quoteRepositoryMock->method('get')->willThrowException($e);
        $closure = function () {
            return;
        };
        $this->cartItemRepositoryInterfacePlugin->aroundGetList($this->cartItemRepository, $closure, -1);
    }
}
