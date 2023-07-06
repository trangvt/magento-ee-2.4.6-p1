<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Model;

use Magento\CompanyCredit\Api\Data\CreditBalanceOptionsInterface;
use Magento\Framework\DataObject;

/**
 * Credit balance data transfer object.
 */
class CreditBalanceOptions extends DataObject implements CreditBalanceOptionsInterface
{
    /**
     * @inheritdoc
     */
    public function getPurchaseOrder()
    {
        return $this->getCustomReferenceNumber();
    }

    /**
     * @inheritdoc
     */
    public function setPurchaseOrder($purchaseOrder)
    {
        $this->setData(self::PURCHASE_ORDER, $purchaseOrder);
        $this->setData(self::CUSTOM_REFERENCE_NUMBER, $purchaseOrder);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCustomReferenceNumber()
    {
        return $this->getData(self::CUSTOM_REFERENCE_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setCustomReferenceNumber($customReferenceNumber)
    {
        return $this->setData(self::CUSTOM_REFERENCE_NUMBER, $customReferenceNumber);
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrement()
    {
        return $this->getData(self::ORDER_INCREMENT);
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrement($orderIncrement)
    {
        return $this->setData(self::ORDER_INCREMENT, $orderIncrement);
    }

    /**
     * @inheritdoc
     */
    public function getCurrencyDisplay()
    {
        return $this->getData(self::CURRENCY_DISPLAY);
    }

    /**
     * @inheritdoc
     */
    public function setCurrencyDisplay($currencyDisplay)
    {
        return $this->setData(self::CURRENCY_DISPLAY, $currencyDisplay);
    }

    /**
     * @inheritdoc
     */
    public function getCurrencyBase()
    {
        return $this->getData(self::CURRENCY_BASE);
    }

    /**
     * @inheritdoc
     */
    public function setCurrencyBase($currencyBase)
    {
        return $this->setData(self::CURRENCY_BASE, $currencyBase);
    }
}
