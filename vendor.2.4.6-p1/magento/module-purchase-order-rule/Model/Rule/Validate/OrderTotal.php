<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule\Validate;

use Magento\Framework\Exception\InputException;
use Magento\PurchaseOrderRule\Model\Rule\ValidateInterface;

/**
 * Order Total condition validate class
 */
class OrderTotal implements ValidateInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $attribute, string $operator, string $value, array $ruleConditions)
    {
        if (!is_numeric($value)) {
            throw new InputException(__('Value must be an integer'));
        }

        if ((int) $value < 0) {
            throw new InputException(__('Value must be positive'));
        }

        $operators = $ruleConditions[$attribute]['operators'];
        if (array_search($operator, array_column($operators, 'value')) === false) {
            throw new InputException(__('Operator is not supported'));
        }
    }
}
