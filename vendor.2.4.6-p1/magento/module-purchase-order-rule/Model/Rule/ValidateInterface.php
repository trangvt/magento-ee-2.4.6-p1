<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule;

use Magento\Framework\Exception\InputException;

/**
 * Validation class required for each type of approval rule
 *
 * @api
 */
interface ValidateInterface
{
    /**
     * Validate the provided condition to ensure it's input is valid
     *
     * @param string $attribute
     * @param string $operator
     * @param string $value
     * @param array $ruleConditions
     * @throws InputException
     */
    public function validate(string $attribute, string $operator, string $value, array $ruleConditions);
}
