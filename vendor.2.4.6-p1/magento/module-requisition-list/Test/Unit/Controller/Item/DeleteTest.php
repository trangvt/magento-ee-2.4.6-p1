<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;

class DeleteTest extends ActionTest
{
    /**
     * @var string
     */
    protected $mockClass = 'Delete';

    /**
     * Prepare requisition list
     */
    protected function prepareRequisitionList()
    {
        $item =
            $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $item->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->requisitionList->expects($this->once())->method('getItems')->willReturn([$item]);
        $this->requisitionList->expects($this->any())->method('setUpdatedAt')->willReturnSelf();
        $this->requisitionListRepository->expects($this->once())->method('get')->willReturn($this->requisitionList);
    }
}
