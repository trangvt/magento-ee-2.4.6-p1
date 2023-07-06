<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\RequisitionList\Model\RequisitionListItem\SaveHandler;
use Magento\RequisitionList\Model\RequisitionListProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SaveHandler.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends TestCase
{
    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var Builder|MockObject
     */
    private $optionsBuilder;

    /**
     * @var RequisitionListManagementInterface|MockObject
     */
    private $requisitionListManagement;

    /**
     * @var Locator|MockObject
     */
    private $requisitionListItemLocator;

    /**
     * @var RequisitionListProduct|MockObject
     */
    private $requisitionListProduct;

    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRegistry;

    /**
     * @var StockItemInterface|MockObject
     */
    private $stockItem;

    /**
     * @var SaveHandler
     */
    private $saveHandler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requisitionListRepository = $this
            ->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->optionsBuilder = $this
            ->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListManagement = $this
            ->getMockBuilder(RequisitionListManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemLocator = $this
            ->getMockBuilder(Locator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListProduct = $this
            ->getMockBuilder(RequisitionListProduct::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockRegistry = $this->getMockForAbstractClass(StockRegistryInterface::class);
        $this->stockItem = $this->getMockForAbstractClass(StockItemInterface::class);
        $this->stockRegistry->method('getStockItemBySku')->willReturn($this->stockItem);

        $objectManager = new ObjectManager($this);
        $this->saveHandler = $objectManager->getObject(
            SaveHandler::class,
            [
                'requisitionListRepository' => $this->requisitionListRepository,
                'optionsBuilder' => $this->optionsBuilder,
                'requisitionListManagement' => $this->requisitionListManagement,
                'requisitionListItemLocator' => $this->requisitionListItemLocator,
                'requisitionListProduct' => $this->requisitionListProduct,
                'stockRegistry' => $this->stockRegistry,
            ]
        );
    }

    /**
     * Test saveItem method
     *
     * @param int|null $itemId
     * @param string $productName
     * @param int $count
     * @param string $rlName
     * @param Phrase $expectedResult
     * @return void
     * @dataProvider saveItemDataProvider
     */
    public function testSaveItem($itemId, $productName, $count, $rlName, Phrase $expectedResult)
    {
        $listId = 1;
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->atLeastOnce())->method('setQty')->willReturnSelf();
        $item->expects($this->atLeastOnce())->method('setOptions')->willReturnSelf();
        $item->expects($this->atLeastOnce())->method('setSku')->willReturnSelf();
        $item->expects($this->atLeastOnce())->method('getId')->willReturn($itemId);
        $requisitionList->expects($this->atLeastOnce())->method('getItems')->willReturn([1 => $item]);
        $requisitionList->expects($this->exactly($count))->method('getName')->willReturn($rlName);
        $this->requisitionListRepository->expects($this->atLeastOnce())->method('get')->with($listId)
            ->willReturn($requisitionList);
        $this->optionsBuilder->expects($this->atLeastOnce())->method('build')->willReturn([]);
        $productData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptions', 'getSku'])
            ->getMock();
        $productData->expects($this->atLeastOnce())->method('getOptions')->with('qty')->willReturn(1);
        $productData->expects($this->atLeastOnce())->method('getSku')->willReturn('sku');
        $this->requisitionListItemLocator->expects($this->atLeastOnce())->method('getItem')->with($itemId)
            ->willReturn($item);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getName')->willReturn($productName);
        $this->requisitionListProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $this->requisitionListManagement->expects($this->atLeastOnce())->method('setItemsToList');
        $this->requisitionListRepository->expects($this->atLeastOnce())
            ->method('save')
            ->willReturn($requisitionList);
        $this->assertEquals($expectedResult, $this->saveHandler->saveItem($productData, [], $itemId, $listId));
    }

    /**
     * Data provider for saveItem method
     *
     * @return array
     */
    public function saveItemDataProvider()
    {
        return [
            [
                1,
                'product name',
                0,
                '',
                new Phrase(
                    '%1 has been updated in your requisition list.',
                    ['product name']
                )
            ],
            [
                null,
                'product name',
                1,
                'requisition list name',
                new Phrase(
                    'Product %1 has been added to the requisition list %2.',
                    ['product name', 'requisition list name']
                )
            ],
        ];
    }

    /**
     * Tests Save requisition list item with decimal qty
     *
     * @param int|float|bool $inputQty
     * @param int|float $qtyToSave
     * @param bool $isQtyDecimal
     * @dataProvider saveItemDecimalQtyDataProvider
     */
    public function testSaveItemDecimalQty($inputQty, $qtyToSave, bool $isQtyDecimal)
    {
        $listId = 1;
        $itemId = 1;
        $sku = 'sku';
        $productName = 'product name';
        $options = [];

        $this->optionsBuilder->method('build')->willReturn($options);

        $this->stockItem->expects($this->once())->method('getIsQtyDecimal')->willReturn($isQtyDecimal);

        /** @var DataObject $productData */
        $productData = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getSku', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        if ($inputQty !== false) {
            $productData->method('getOptions')->with('qty')->willReturn($inputQty);
        }
        $productData->method('getSku')->willReturn($sku);

        $item = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $item->expects($this->once())->method('setQty')->with($qtyToSave)->willReturnSelf();
        $item->method('setOptions')->with($options)->willReturnSelf();
        $item->method('setSku')->willReturnSelf();
        $item->method('getId')->willReturn($itemId);
        $this->requisitionListItemLocator->method('getItem')->with($itemId)->willReturn($item);

        $requisitionList = $this->getMockForAbstractClass(RequisitionListInterface::class);
        $requisitionList->method('getItems')->willReturn([$item]);
        $this->requisitionListRepository->method('get')->with($listId)->willReturn($requisitionList);

        $product = $this->getMockForAbstractClass(ProductInterface::class);
        $product->method('getName')->willReturn($productName);
        $this->requisitionListProduct->method('getProduct')->willReturn($product);
        $this->requisitionListRepository->method('save')->with($requisitionList);

        $expectedMessage = new Phrase('%1 has been updated in your requisition list.', [$productName]);

        $this->assertEquals($expectedMessage, $this->saveHandler->saveItem($productData, [], $itemId, $listId));
    }

    /**
     * Data provider fot Save requisition list item with decimal qty
     *
     * @return array
     */
    public function saveItemDecimalQtyDataProvider(): array
    {
        return [
            [false, 1, false],
            [0, 1, false],
            [-1, 1, false],
            [2.5, 2, false],
            [2.5, 2.5, true],
        ];
    }
}
