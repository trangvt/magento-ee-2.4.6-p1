<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\AddToCartProcessorInterface;
use Magento\RequisitionList\Model\RequisitionListItem\CartItemConverter;
use Magento\RequisitionList\Model\RequisitionListItem\Merger;
use Magento\RequisitionList\Model\RequisitionListItem\Validation;
use Magento\RequisitionList\Model\RequisitionListManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for RequisitionListManagement.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequisitionListManagementTest extends TestCase
{
    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var RequisitionListItemInterfaceFactory|MockObject
     */
    private $requisitionListItemFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * @var CartItemConverter|MockObject
     */
    private $cartItemConverter;

    /**
     * @var Validation|MockObject
     */
    private $validation;

    /**
     * @var Merger|MockObject
     */
    private $itemMerger;

    /**
     * @var RequisitionListItemInterface|MockObject
     */
    private $requisitionListItem;

    /**
     * @var DateTime|MockObject
     */
    private $dateTime;

    /**
     * @var AddToCartProcessorInterface|MockObject
     */
    private $addToCartProcessor;

    /**
     * @var AddToCartProcessorInterface|MockObject
     */
    private $giftCardAddToCartProcessor;

    /**
     * @var RequisitionListManagement
     */
    private $requisitionListManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionListRepository = $this->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemFactory = $this->getMockBuilder(RequisitionListItemInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartItemConverter = $this
            ->getMockBuilder(CartItemConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validation = $this->getMockBuilder(Validation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemMerger = $this->getMockBuilder(Merger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dateTime = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addToCartProcessor = $this
            ->getMockBuilder(AddToCartProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->giftCardAddToCartProcessor = $this
            ->getMockBuilder(AddToCartProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addToCartProcessors = [
            'simple' => $this->addToCartProcessor,
            'giftcard' => $this->giftCardAddToCartProcessor
        ];

        $objectManager = new ObjectManager($this);
        $this->requisitionListManagement = $objectManager->getObject(
            RequisitionListManagement::class,
            [
                'requisitionListRepository' => $this->requisitionListRepository,
                'requisitionListItemFactory' => $this->requisitionListItemFactory,
                'cartRepository' => $this->cartRepository,
                'cartItemConverter' => $this->cartItemConverter,
                'validation' => $this->validation,
                'itemMerger' => $this->itemMerger,
                'dateTime' => $this->dateTime,
                'addToCartProcessors' => $addToCartProcessors,
            ]
        );
    }

    /**
     * Test for addItemToList method.
     *
     * @return void
     */
    public function testAddItemToList()
    {
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionList->expects($this->atLeastOnce())->method('getItems')->willReturn([]);
        $this->itemMerger->expects($this->atLeastOnce())
            ->method('mergeItem')->with([], $this->requisitionListItem)->willReturn([$this->requisitionListItem]);
        $requisitionList->expects($this->once())
            ->method('setItems')->with([$this->requisitionListItem])->willReturnSelf();
        $this->requisitionListRepository->expects($this->once())
            ->method('save')->with($requisitionList)->willReturnSelf();
        $this->assertEquals(
            $requisitionList,
            $this->requisitionListManagement->addItemToList($requisitionList, $this->requisitionListItem)
        );
    }

    /**
     * Test for setItemsToList method.
     *
     * @return void
     */
    public function testSetItemsToList()
    {
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemMerger->expects($this->atLeastOnce())
            ->method('merge')->with([$this->requisitionListItem])->willReturnArgument(0);
        $requisitionList->expects($this->once())
            ->method('setItems')->with([$this->requisitionListItem])->willReturnSelf();

        $this->assertEquals(
            $requisitionList,
            $this->requisitionListManagement->setItemsToList($requisitionList, [$this->requisitionListItem])
        );
    }

    /**
     * Test for copyItemToList method.
     *
     * @return void
     */
    public function testCopyItemToList()
    {
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionList->expects($this->atLeastOnce())->method('getItems')->willReturn([]);

        $listItem = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->requisitionListItem);

        $this->itemMerger->expects($this->atLeastOnce())
            ->method('mergeItem')->with([], $this->requisitionListItem)->willReturn([$this->requisitionListItem]);
        $requisitionList->expects($this->once())
            ->method('setItems')->with([$this->requisitionListItem])->willReturnSelf();

        $this->assertEquals(
            $requisitionList,
            $this->requisitionListManagement->copyItemToList($requisitionList, $listItem)
        );
    }

    /**
     * Test for placeItemsInCart method.
     *
     * @param string $productType
     * @param int $productsAddedToCart
     * @param int $giftCardsAddedToCart
     * @return void
     * @dataProvider placeItemInCartDataProvider
     */
    public function testPlaceItemsInCart($productType, $productsAddedToCart, $giftCardsAddedToCart)
    {
        $listId = 1;

        $cart = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'removeAllItems', 'collectTotals', 'addProduct'])
            ->getMockForAbstractClass();
        $cart->expects($this->any())->method('getId')->willReturn(1);
        $cart->expects($this->any())->method('addProduct')->willReturnSelf();
        $cart->expects($this->atLeastOnce())->method('removeAllItems')->willReturnSelf();
        $this->cartRepository->expects($this->atLeastOnce())->method('get')->willReturn($cart);
        $this->validation->expects($this->atLeastOnce())->method('isValid')->willReturn(true);
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId'])
            ->getMockForAbstractClass();

        $this->cartItemConverter->expects($this->atLeastOnce())->method('convert')->willReturn($cartItem);
        $product->expects($this->atLeastOnce())->method('getTypeId')->willReturn($productType);
        $cartItem->expects($this->atLeastOnce())->method('getData')->with('product')->willReturn($product);

        $this->requisitionListItem->expects($this->atLeastOnce())
            ->method('getRequisitionListId')
            ->willReturn($listId);

        $list = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requisitionListRepository->expects($this->atLeastOnce())
            ->method('get')
            ->with($listId)
            ->willReturn($list);
        $list->expects($this->atLeastOnce())
            ->method('setUpdatedAt')
            ->willReturnSelf();
        $this->requisitionListRepository->expects($this->atLeastOnce())
            ->method('save')
            ->with($list)
            ->willReturn($list);
        $this->addToCartProcessor->expects($this->exactly($productsAddedToCart))->method('execute')
            ->with($cart, $cartItem);
        $this->giftCardAddToCartProcessor->expects($this->exactly($giftCardsAddedToCart))->method('execute')
            ->with($cart, $cartItem);

        $this->assertEquals(
            [$this->requisitionListItem],
            $this->requisitionListManagement->placeItemsInCart(1, [$this->requisitionListItem], true)
        );
    }

    /**
     * DataProvider for testPlaceItemsInCart().
     *
     * @return array
     */
    public function placeItemInCartDataProvider()
    {
        return [
            ['simple', 1, 0],
            ['giftcard', 0, 1]
        ];
    }
}
