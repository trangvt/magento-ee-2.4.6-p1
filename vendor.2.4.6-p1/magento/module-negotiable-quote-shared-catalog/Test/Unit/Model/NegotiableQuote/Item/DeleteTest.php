<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\NegotiableQuoteSharedCatalog\Test\Unit\Model\NegotiableQuote\Item;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Quote\ItemRemove;
use Magento\NegotiableQuoteSharedCatalog\Model\NegotiableQuote\Item\Delete;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Delete model.
 */
class DeleteTest extends TestCase
{
    /**
     * @var ItemRemove|MockObject
     */
    private $itemRemove;

    /**
     * @var CartItemRepositoryInterface|MockObject
     */
    private $quoteItemRepository;

    /**
     * @var Delete
     */
    private $itemDelete;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteItemRepository = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();
        $this->itemRemove = $this
            ->getMockBuilder(ItemRemove::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->itemDelete = $objectManager->getObject(
            Delete::class,
            [
                'itemRemove' => $this->itemRemove,
                'quoteItemRepository' => $this->quoteItemRepository,
            ]
        );
    }

    /**
     * Test for deleteItems method.
     *
     * @return void
     */
    public function testDeleteItems()
    {
        $quoteId = 1;
        $quoteItemId = 2;
        $productId = 3;
        $sku = 'sku';
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductId', 'getOrigData', 'getItemId', 'getSku'])
            ->getMock();
        $quoteItem->expects($this->atLeastOnce())->method('getOrigData')->with('quote_id')->willReturn($quoteId);
        $quoteItem->expects($this->once())->method('getItemId')->willReturn($quoteItemId);
        $quoteItem->expects($this->atLeastOnce())->method('getProductId')->willReturn($productId);
        $quoteItem->expects($this->atLeastOnce())->method('getSku')->willReturn($sku);
        $this->quoteItemRepository->expects($this->once())->method('deleteById')->with($quoteId, $quoteItemId);
        $this->itemRemove->expects($this->once())->method('setNotificationRemove')->with($quoteId, $productId, [$sku]);

        $this->itemDelete->deleteItems([$quoteItem]);
    }
}
