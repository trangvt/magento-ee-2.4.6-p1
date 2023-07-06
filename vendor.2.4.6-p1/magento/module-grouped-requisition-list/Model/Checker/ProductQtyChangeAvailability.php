<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GroupedRequisitionList\Model\Checker;

use Magento\RequisitionList\Model\Checker\ProductQtyChangeAvailabilityInterface;

/**
 * Responsible for checking availability of requisition list item 'Qty' input.
 */
class ProductQtyChangeAvailability implements ProductQtyChangeAvailabilityInterface
{
    /**
     * @inheritdoc
     */
    public function isAvailable(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        return $product->getTypeId() !== \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE;
    }
}
