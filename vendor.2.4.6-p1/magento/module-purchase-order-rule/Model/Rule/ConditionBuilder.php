<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Framework\Api\AbstractSimpleObjectBuilder;

/**
 * Builder to create new condition objects
 */
class ConditionBuilder extends AbstractSimpleObjectBuilder
{
    /**
     * @inheritDoc
     */
    public function setType(string $type)
    {
        return $this->_set(ConditionInterface::KEY_TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(?string $attribute)
    {
        return $this->_set(ConditionInterface::KEY_ATTRIBUTE, $attribute);
    }

    /**
     * @inheritDoc
     */
    public function setOperator(?string $operator)
    {
        return $this->_set(ConditionInterface::KEY_OPERATOR, $operator);
    }

    /**
     * @inheritDoc
     */
    public function setValue($value)
    {
        return $this->_set(ConditionInterface::KEY_VALUE, $value);
    }

    /**
     * @inheritDoc
     */
    public function setCurrencyCode(string $currencyCode)
    {
        return $this->_set(ConditionInterface::KEY_CURRENCY_CODE, $currencyCode);
    }

    /**
     * @inheritDoc
     */
    public function setIsValueProcessed(?bool $isValueProcessed)
    {
        return $this->_set(ConditionInterface::KEY_IS_VALUE_PROCESSED, $isValueProcessed);
    }

    /**
     * @inheritDoc
     */
    public function setAggregator(?string $aggregator)
    {
        return $this->_set(ConditionInterface::KEY_AGGREGATOR, $aggregator);
    }

    /**
     * Add a condition into the built condition
     *
     * @param ConditionInterface $condition
     * @return ConditionBuilder
     */
    public function addCondition(ConditionInterface $condition)
    {
        $this->data[ConditionInterface::KEY_CONDITIONS][] = $condition;
        return $this;
    }
}
