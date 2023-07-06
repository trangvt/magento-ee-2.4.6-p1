<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;
use Magento\PurchaseOrderRule\Model\Rule\Condition\Combine;

/**
 * Create condition combination
 */
class CreateCondition
{
    private const OPERATORS = [
        'MORE_THAN' => '>',
        'LESS_THAN' => '<',
        'MORE_THAN_OR_EQUAL_TO' => '>=',
        'LESS_THAN_OR_EQUAL_TO' => '<='
    ];
    /**
     * @var RuleConditionPool
     */
    private RuleConditionPool $ruleConditionPool;

    /**
     * @var ConditionInterfaceFactory
     */
    private ConditionInterfaceFactory $conditionFactory;

    /**
     * @param RuleConditionPool $ruleConditionPool
     * @param ConditionInterfaceFactory $conditionFactory
     */
    public function __construct(
        RuleConditionPool $ruleConditionPool,
        ConditionInterfaceFactory $conditionFactory
    ) {
        $this->ruleConditionPool = $ruleConditionPool;
        $this->conditionFactory = $conditionFactory;
    }

    /**
     * Create combined condition based on the input data
     *
     * @param array $conditionsData
     * @return ConditionInterface
     * @throws InvalidArgumentException
     */
    public function execute(array $conditionsData): ConditionInterface
    {
        $conditions = [];
        foreach ($conditionsData as $conditionData) {
            $conditions[] = $this->getCondition($conditionData);
        }
        return $this->conditionFactory->create(
            [
                'data' => [
                    ConditionInterface::KEY_TYPE => Combine::class,
                    ConditionInterface::KEY_IS_VALUE_PROCESSED => null,
                    ConditionInterface::KEY_AGGREGATOR => 'all',
                    ConditionInterface::KEY_CONDITIONS => $conditions,
                    ConditionInterface::KEY_VALUE => '1',
                    ConditionInterface::KEY_OPERATOR => null,
                    ConditionInterface::KEY_ATTRIBUTE => null
                ]
            ]
        );
    }

    /**
     * Create condition instance from data
     *
     * @param array $conditionData
     * @return ConditionInterface
     * @throws InvalidArgumentException
     */
    private function getCondition(array $conditionData): ConditionInterface
    {
        $attribute = isset($conditionData['attribute']) ? strtolower((string)$conditionData['attribute']) : null;
        $operator = isset($conditionData['operator'])
            ? self::OPERATORS[$conditionData['operator']] ?? $conditionData['operator']
            : null;
        $value = $conditionData['value']
            ?? $conditionData['amount']['value']
            ?? $conditionData['quantity']['value']
            ?? null;
        $currencyCode = $conditionData['currency_code']
            ?? $conditionData['amount']['currency']
            ?? '';

        $conditionRule = $this->ruleConditionPool->getType($attribute);

        if (!$conditionRule) {
            throw new InvalidArgumentException(__('Unknown condition type: %type', ['type' => $attribute]));
        }

        return $this->conditionFactory->create(
            [
                'data' => [
                    ConditionInterface::KEY_TYPE => get_class($conditionRule),
                    ConditionInterface::KEY_ATTRIBUTE => $attribute,
                    ConditionInterface::KEY_OPERATOR => $operator,
                    ConditionInterface::KEY_VALUE => $value,
                    ConditionInterface::KEY_CURRENCY_CODE => (string) $currencyCode,
                    ConditionInterface::KEY_IS_VALUE_PROCESSED => false
                ]
            ]
        );
    }
}
