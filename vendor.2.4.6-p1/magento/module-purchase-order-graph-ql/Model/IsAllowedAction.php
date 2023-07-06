<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorLocator;

/**
 * Check if action is allowed on current purchase order
 */
class IsAllowedAction
{
    /**
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @var ValidatorLocator
     */
    private ValidatorLocator $validatorLocator;

    /**
     * @param Authorization $authorization
     * @param ValidatorLocator $validatorLocator
     */
    public function __construct(
        Authorization $authorization,
        ValidatorLocator $validatorLocator
    ) {
        $this->authorization = $authorization;
        $this->validatorLocator = $validatorLocator;
    }

    /**
     * Executes the validation.
     *
     * @param string $action
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(string $action, PurchaseOrderInterface $purchaseOrder): bool
    {
        return $this->validatorLocator->getValidator($action)->validate($purchaseOrder)
            && $this->authorization->isAllowed($action, $purchaseOrder);
    }
}
