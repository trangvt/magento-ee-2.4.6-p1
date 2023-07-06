<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Validator\ActionReady;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorInterface;

/**
 * Validates purchase order is ready for placing order
 */
class ValidatePurchaseOrderValidator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(PurchaseOrderInterface $purchaseOrder) : bool
    {
        $status = $purchaseOrder->getStatus();
        $allowedStatuses = [
            PurchaseOrderInterface::STATUS_PENDING
        ];
        if (in_array($status, $allowedStatuses)) {
            return true;
        }
        return false;
    }
}
