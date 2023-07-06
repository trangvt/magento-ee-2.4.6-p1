<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block\RuleFieldset;

use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;

/**
 * Purchase order block class
 *
 * @api
 */
class ViewCondition extends Template
{
    /**
     * @var Condition
     */
    private $conditionBlock;

    /**
     * @var RuleConditionPool
     */
    private $ruleConditionPool;

    /**
     * @var string
     */
    private $conditionKey;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * ViewCondition constructor.
     *
     * @param Template\Context $context
     * @param Condition $conditionBlock
     * @param RuleConditionPool $ruleConditionPool
     * @param PriceCurrencyInterface $priceCurrency
     * @param string|null $conditionKey
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Condition $conditionBlock,
        RuleConditionPool $ruleConditionPool,
        PriceCurrencyInterface $priceCurrency,
        string $conditionKey = null,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->conditionBlock = $conditionBlock;
        $this->ruleConditionPool = $ruleConditionPool;
        $this->conditionKey = $conditionKey ? $conditionKey : Condition::CONDITION_KEY;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get rule condition label
     *
     * @return string
     */
    public function getConditionLabel() : string
    {
        foreach ($this->ruleConditionPool->getConditions() as $condition) {
            if (in_array($this->conditionKey, $condition)) {
                return (string) $condition['label'];
            }
        }
        return '';
    }

    /**
     * Check if condition type is active
     *
     * @param $conditions
     * @return bool
     */
    public function isConditionSelected()
    {
        return in_array($this->conditionKey, $this->getParentBlock()->getPurchaseOrderRuleData()['conditions'][0]);
    }

    /**
     * Retrieve purchase order rule data
     *
     * @return array
     */
    public function getPurchaseOrderRuleCondition()
    {
        if ($this->isConditionSelected()) {
            $result = $this->getParentBlock()->getPurchaseOrderRuleData()['conditions'];
        } else {
            $result =  [
                [
                    'attribute' => '',
                    'operator' => '>',
                    'value' => '',
                    'currency_code' => ''
                ]
            ];
        }
        return $result;
    }

    /**
     * Get selected condition operator.
     *
     * @return string
     */
    public function getSelectedOperator(): string
    {
        $key = $this->conditionBlock->getConditionKey();
        $operators = $this->ruleConditionPool->getOperatorsByConditionKey($key);
        $ruleCondition = $this->getPurchaseOrderRuleCondition();
        foreach ($operators as $operator) {
            if ($ruleCondition[0]['operator'] === $operator['value']) {
                return $operator['label'];
            }
        }
        return '';
    }

    /**
     * Get symbol of a selected currency.
     *
     * @return string
     */
    public function getSelectedCurrency(): string
    {
        $currencies = $this->conditionBlock->getCurrencies();
        $ruleCondition = $this->getPurchaseOrderRuleCondition();
        $currencyCode = $ruleCondition[0]['currency_code'];
        if (empty($currencyCode)) {
            return '';
        }
        if (key_exists($currencyCode, $currencies)) {
            return $currencies[$currencyCode];
        } else {
            return $this->priceCurrency->getCurrencySymbol(ScopeInterface::SCOPE_DEFAULT, $currencyCode);
        }
    }
}
