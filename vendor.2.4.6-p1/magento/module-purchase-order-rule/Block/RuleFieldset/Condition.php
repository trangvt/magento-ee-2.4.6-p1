<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block\RuleFieldset;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Purchase order block abstract class
 *
 * @api
 */
class Condition extends Template
{
    /**
     * Conditions attribute
     */
    const CONDITION_KEY = 'grand_total';

    /**
     * @var RuleConditionPool
     */
    private $ruleConditionPool;

    /**
     * @var PriceCurrencyInterface
     */
    private $amountCurrency;

    /**
     * @var string
     */
    private $conditionKey;

    /**
     * @param TemplateContext $context
     * @param RuleConditionPool $ruleConditionPool
     * @param PriceCurrencyInterface $amountCurrency
     * @param string $conditionKey
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        RuleConditionPool $ruleConditionPool,
        PriceCurrencyInterface $amountCurrency,
        string $conditionKey = null,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->ruleConditionPool = $ruleConditionPool;
        $this->amountCurrency = $amountCurrency;
        $this->conditionKey = $conditionKey ? $conditionKey : self::CONDITION_KEY;
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
     * Get rule conditions key
     *
     * @return string
     */
    public function getConditionKey()
    {
        return $this->conditionKey;
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
     * Retrieve currencies array
     *
     * Return array: currency code => currency symbol
     *
     * @return array
     */
    public function getCurrencies() : array
    {
        $currencies = [];
        foreach ($this->_storeManager->getWebsites() as $website) {
            $currencyCode = $website->getBaseCurrencyCode();
            $currencies[$currencyCode] = $this->amountCurrency->getCurrencySymbol(null, $currencyCode);
        }
        return $currencies;
    }

    /**
     * Get condition operators by condition key.
     *
     * @param string $key
     * @return array
     */
    public function getOperatorsByConditionKey(string $key): array
    {
        return $this->ruleConditionPool->getOperatorsByConditionKey($key);
    }
}
