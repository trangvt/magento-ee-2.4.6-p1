<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Represent a serialized condition as a simple object
 */
class Condition extends AbstractSimpleObject implements ConditionInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param array $data
     * @param Json $serializer
     */
    public function __construct(
        array $data,
        Json $serializer
    ) {
        parent::__construct($data);
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type)
    {
        return $this->setData(ConditionInterface::KEY_TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getType() : ?string
    {
        return $this->_get(ConditionInterface::KEY_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $attribute)
    {
        return $this->setData(ConditionInterface::KEY_ATTRIBUTE, $attribute);
    }

    /**
     * @inheritDoc
     */
    public function getAttribute() : ?string
    {
        return $this->_get(ConditionInterface::KEY_ATTRIBUTE);
    }

    /**
     * @inheritDoc
     */
    public function setOperator(string $operator)
    {
        return $this->setData(ConditionInterface::KEY_OPERATOR, $operator);
    }

    /**
     * @inheritDoc
     */
    public function getOperator() : ?string
    {
        return $this->_get(ConditionInterface::KEY_OPERATOR);
    }

    /**
     * @inheritDoc
     */
    public function setValue($value)
    {
        return $this->setData(ConditionInterface::KEY_VALUE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->_get(ConditionInterface::KEY_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setCurrencyCode(string $currencyCode)
    {
        return $this->setData(ConditionInterface::KEY_CURRENCY_CODE, $currencyCode);
    }

    /**
     * @inheritDoc
     */
    public function getCurrencyCode() : ?string
    {
        return $this->_get(ConditionInterface::KEY_CURRENCY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setIsValueProcessed(bool $isValueProcessed)
    {
        return $this->setData(ConditionInterface::KEY_IS_VALUE_PROCESSED, $isValueProcessed);
    }

    /**
     * @inheritDoc
     */
    public function getIsValueProcessed() : ?bool
    {
        return $this->_get(ConditionInterface::KEY_IS_VALUE_PROCESSED);
    }

    /**
     * @inheritDoc
     */
    public function setAggregator(string $aggregator)
    {
        return $this->setData(ConditionInterface::KEY_AGGREGATOR, $aggregator);
    }

    /**
     * @inheritDoc
     */
    public function getAggregator() : ?string
    {
        return $this->_get(ConditionInterface::KEY_AGGREGATOR);
    }

    /**
     * @inheritDoc
     */
    public function addCondition(ConditionInterface $condition)
    {
        $conditions = $this->_get(ConditionInterface::KEY_CONDITIONS);
        if (!$conditions) {
            $conditions = [];
        }
        $conditions[] = $condition;
        return $this->setData(ConditionInterface::KEY_CONDITIONS, $condition);
    }

    /**
     * @inheritDoc
     */
    public function getConditions() : array
    {
        return $this->_get(ConditionInterface::KEY_CONDITIONS) ?? [];
    }

    /**
     * Return a serialized version of the condition
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->serializer->serialize($this->__toArray());
    }
}
