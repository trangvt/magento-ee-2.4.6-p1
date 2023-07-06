<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Api\Data;

/**
 * Credit balance data transfer object interface.
 *
 * @api
 * @since 100.0.0
 */
interface CreditBalanceOptionsInterface
{
    const PURCHASE_ORDER = 'purchase_order';
    const CUSTOM_REFERENCE_NUMBER = 'custom_reference_number';
    const ORDER_INCREMENT = 'order_increment';
    const CURRENCY_DISPLAY = 'currency_display';
    const CURRENCY_BASE = 'currency_base';

    /**
     * Get purchase order number.
     *
     * @return string
     * @deprecated 100.2.0 Use getCustomReferenceNumber
     */
    public function getPurchaseOrder();

    /**
     * Set purchase order number.
     *
     * @param string $purchaseOrder
     * @return $this
     * @deprecated 100.2.0 Use setCustomReferenceNumber
     */
    public function setPurchaseOrder($purchaseOrder);

    /**
     * Get Custom Reference number.
     *
     * @return string|null
     * @since 100.2.0
     */
    public function getCustomReferenceNumber();

    /**
     * Set Custom Reference number.
     *
     * @param string $customReferenceNumber
     * @return $this
     * @since 100.2.0
     */
    public function setCustomReferenceNumber($customReferenceNumber);

    /**
     * Get order increment.
     *
     * @return string
     */
    public function getOrderIncrement();

    /**
     * Set order increment.
     *
     * @param string $orderIncrement
     * @return $this
     */
    public function setOrderIncrement($orderIncrement);

    /**
     * Get currency display.
     *
     * @return string
     */
    public function getCurrencyDisplay();

    /**
     * Set the currency display from the order.
     *
     * @param bool $currencyDisplay
     * @return $this
     */
    public function setCurrencyDisplay($currencyDisplay);

    /**
     * Get currency base.
     *
     * @return string
     */
    public function getCurrencyBase();

    /**
     * Set currency base from the order.
     *
     * @param bool $currencyBase
     * @return $this
     */
    public function setCurrencyBase($currencyBase);
}
