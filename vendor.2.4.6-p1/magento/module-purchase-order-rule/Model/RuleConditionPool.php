<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrderRule\Model\Rule\ValidateInterface;
use Psr\Log\LoggerInterface;

/**
 * A pool containing all potential rule conditions
 */
class RuleConditionPool
{
    /**
     * @var array
     */
    private $ruleConditions;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param array $ruleConditions
     */
    public function __construct(
        LoggerInterface $logger,
        array $ruleConditions
    ) {
        $this->ruleConditions = $ruleConditions;
        $this->logger = $logger;
    }

    /**
     * Validate the rule condition using its validate class
     *
     * @param string $attribute
     * @param string $operator
     * @param string $value
     * @throws LocalizedException
     */
    public function validateRuleCondition(string $attribute, string $operator, string $value)
    {
        if (!isset($this->ruleConditions[$attribute]) || !isset($this->ruleConditions[$attribute]['validate'])) {
            throw new LocalizedException(__('Rule condition does not exist.'));
        }

        // Verify the validate class implements the correct interface
        if (!$this->ruleConditions[$attribute]['validate'] instanceof ValidateInterface) {
            $this->logger->error(' Purchase Order rule condition "' . $attribute . '" validation class not ' .
                'configured correctly. It must implement ValidateInterface.');

            throw new LocalizedException(__('Rule condition does not exist.'));
        }

        /* @var ValidateInterface $validateClass */
        $validateClass = $this->ruleConditions[$attribute]['validate'];

        try {
            $validateClass->validate($attribute, $operator, $value, $this->ruleConditions);
        } catch (InputException $e) {
            throw new LocalizedException(__('Rule is incorrectly configured.'));
        }
    }

    /**
     * Retrieve the type for the condition
     *
     * @param string $attribute
     * @return string
     */
    public function getType(string $attribute)
    {
        if (!isset($this->ruleConditions[$attribute]) || !isset($this->ruleConditions[$attribute]['type'])) {
            return null;
        }

        return $this->ruleConditions[$attribute]['type'];
    }

    /**
     * Retrieve conditions label and operators
     *
     * @return array
     */
    public function getConditions()
    {
        $conditions = [];

        foreach ($this->ruleConditions as $key => $value) {
            $conditions[] = [
                'label' => $value['label'],
                'value' => $key,
                'operators' => $value['operators']
            ];
        }

        return $conditions;
    }

    /**
     * Get condition operators by condition key.
     *
     * @param string $key
     * @return array
     */
    public function getOperatorsByConditionKey(string $key): array
    {
        $conditions = $this->getConditions();
        $conditionKey = array_search($key, array_column($conditions, 'value'));

        if ($conditionKey === false) {
            return [];
        }

        return $conditions[$conditionKey]['operators'];
    }
}
