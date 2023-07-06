<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Model\Checker;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Interface for checking availability of edit requisition list product qty.
 *
 * @api
 */
interface ProductQtyChangeAvailabilityInterface
{
    /**
     * Check if product qty for requisition list item can be changed.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isAvailable(ProductInterface $product);
}
