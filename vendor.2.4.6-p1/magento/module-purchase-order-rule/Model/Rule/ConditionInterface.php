<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

/**
 * Condition interface
 *
 * @api
 */
interface ConditionInterface
{
    const KEY_TYPE = 'type';

    const KEY_ATTRIBUTE = 'attribute';

    const KEY_OPERATOR = 'operator';

    const KEY_VALUE = 'value';

    const KEY_IS_VALUE_PROCESSED = 'is_value_processed';

    const KEY_AGGREGATOR = 'aggregator';

    const KEY_CONDITIONS = 'conditions';

    const KEY_CURRENCY_CODE = 'currency_code';

    /**
     * Set the type of the condition
     *
     * @param string $type
     * @return mixed
     */
    public function setType(string $type);

    /**
     * Retrieve the conditions type
     *
     * @return string
     */
    public function getType() : ?string;

    /**
     * Set the attribute for the condition
     *
     * @param string $attribute
     * @return mixed
     */
    public function setAttribute(string $attribute);

    /**
     * Retrieve the attribute for the condition
     *
     * @return string
     */
    public function getAttribute() : ?string;

    /**
     * Set the operator for the condition
     *
     * @param string $operator
     * @return mixed
     */
    public function setOperator(string $operator);

    /**
     * Retrieve the operator for the condition
     *
     * @return string
     */
    public function getOperator() : ?string;

    /**
     * Set the value for the condition
     *
     * @param mixed $value
     * @return mixed
     */
    public function setValue($value);

    /**
     * Retrieve the value for the condition
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Set whether the rule should be processed or not
     *
     * @param bool $isValueProcessed
     * @return mixed
     */
    public function setIsValueProcessed(bool $isValueProcessed);

    /**
     * Determine if the rule should be processed or not
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsValueProcessed() : ?bool;

    /**
     * Set the aggregator for the rule
     *
     * @param string $aggregator
     * @return mixed
     */
    public function setAggregator(string $aggregator);

    /**
     * Retrieve the conditions aggregator method
     *
     * @return mixed
     */
    public function getAggregator();

    /**
     * Add a child condition into the condition
     *
     * @param ConditionInterface $condition
     * @return mixed
     */
    public function addCondition(ConditionInterface $condition);

    /**
     * Retrieve all child conditions for the rule
     *
     * @return array
     */
    public function getConditions() : array;

    /**
     * Set rule currency code
     *
     * @param string $currencyCode
     * @return mixed
     */
    public function setCurrencyCode(string $currencyCode);

    /**
     * Set rule currency code
     *
     * @return null|string
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getCurrencyCode() : ?string;
}
