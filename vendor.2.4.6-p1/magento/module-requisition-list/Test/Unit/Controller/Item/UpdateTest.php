<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;

class UpdateTest extends ActionTest
{
    /**
     * @var string
     */
    protected $mockClass = 'Update';

    /**
     * @inheritdoc
     */
    protected function prepareRequisitionList()
    {
        $sku = 'sku';
        $qty = 2.5;

        $stockItem = $this->getMockForAbstractClass(StockItemInterface::class);
        $this->stockRegistry->method('getStockItemBySku')->with($sku)->willReturn($stockItem);
        $stockItem->method('getIsQtyDecimal')->willReturn(true);

        $item = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('setQty')->with($qty)->willReturnSelf();
        $this->requisitionList->expects($this->once())->method('getItems')->willReturn([$item]);
        $this->requisitionList->method('setUpdatedAt')->willReturnSelf();
        $this->requisitionListRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->requisitionList);
    }

    /**
     * @inheritdoc
     */
    protected function prepareRequest()
    {
        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['requisition_id', null, 1],
                ['selected', null, '1, 2, 3, 4, 5'],
                ['qty', null, ['sku' => 2.5]]
            ]
        );
    }
}
