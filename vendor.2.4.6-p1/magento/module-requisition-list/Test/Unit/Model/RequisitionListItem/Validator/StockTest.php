<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem\Validator;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\RequisitionList\Model\RequisitionListItem\Validator\Stock;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Stock.
 */
class StockTest extends TestCase
{
    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRegistryMock;

    /**
     * @var RequisitionListItemProduct|MockObject
     */
    private $requisitionListItemProduct;

    /**
     * @var StockStateInterface|MockObject
     */
    private $stockStateMock;

    /**
     * @var Stock
     */
    private $stockValidator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->stockRegistryMock = $this->getMockForAbstractClass(StockRegistryInterface::class);
        $this->requisitionListItemProduct = $this->createMock(RequisitionListItemProduct::class);
        $this->stockStateMock = $this->getMockForAbstractClass(StockStateInterface::class);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->stockValidator = $objectManagerHelper->getObject(
            Stock::class,
            [
                'stockRegistry' => $this->stockRegistryMock,
                'requisitionListItemProduct' => $this->requisitionListItemProduct,
                'stockState' => $this->stockStateMock,
            ]
        );
    }

    /**
     * Test for validate method.
     *
     * @param int $stockStatus
     * @param float $stockQty
     * @param float $itemQty
     * @param int $getItemQtyInvokesCount
     * @param int $isProductCompositeInvokesCount
     * @param int $getStockItemQtyInvokesCount
     * @param bool $isValid
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        int $stockStatus,
        float $stockQty,
        float $itemQty,
        int $getItemQtyInvokesCount,
        int $isProductCompositeInvokesCount,
        int $getStockItemQtyInvokesCount,
        bool $isValid
    ) {
        $productId = 666;
        $itemMock = $this->createMock(RequisitionListItem::class);
        $itemMock->expects($this->exactly($getItemQtyInvokesCount))
            ->method('getQty')
            ->willReturn($itemQty);

        $productId = 666;
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isComposite'])
            ->getMockForAbstractClass();
        $productMock->method('getId')
            ->willReturn($productId);
        $productMock->expects($this->exactly($isProductCompositeInvokesCount))
            ->method('isComposite')
            ->willReturn(false);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($productMock);

        $stockStatusMock = $this->getMockForAbstractClass(StockStatusInterface::class);
        $stockStatusMock->expects($this->atLeastOnce())
            ->method('getStockStatus')
            ->willReturn($stockStatus);
        $this->stockRegistryMock->expects($this->atLeastOnce())
            ->method('getStockStatus')
            ->willReturn($stockStatusMock);
        $this->stockStateMock->expects($this->exactly($getStockItemQtyInvokesCount))
            ->method('checkQty')
            ->with($productId, $itemQty)
            ->willReturn($stockQty >= $itemQty);
        $errors = $this->stockValidator->validate($itemMock);

        $this->assertEquals($isValid, empty($errors));
    }

    /**
     * Data provider for validate.
     *
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                0,
                0,
                1,
                0,
                0,
                0,
                false
            ],
            [
                1,
                10,
                11,
                1,
                1,
                1,
                false
            ],
            [
                1,
                11,
                10,
                1,
                0,
                1,
                true
            ]
        ];
    }
}
