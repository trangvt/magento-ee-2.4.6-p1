<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Plugin\Cart;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\IsActive;

/**
 * Plugin to consider cart as active if it's related to purchase order in approved_pending_payment status
 */
class IsActivePlugin
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    /**
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Consider cart as active if it's related to purchase order in approved_pending_payment status
     *
     * @param IsActive $subject
     * @param \Closure $proceed
     * @param CartInterface $cart
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(IsActive $subject, \Closure $proceed, CartInterface $cart): bool
    {
        $purchaseOrder = $this->purchaseOrderRepository->getByQuoteId($cart->getId());
        if ($purchaseOrder->getEntityId()
            && $purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT
        ) {
            return true;
        }
        return $proceed($cart);
    }
}
