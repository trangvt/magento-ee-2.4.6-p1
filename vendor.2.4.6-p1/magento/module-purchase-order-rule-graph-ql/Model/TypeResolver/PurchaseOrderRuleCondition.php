<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRuleGraphQl\Model\TypeResolver;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolves PurchaseOrderRuleConditionInterface type
 */
class PurchaseOrderRuleCondition implements TypeResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        return isset($data['amount'])
            ? 'PurchaseOrderApprovalRuleConditionAmount'
            : 'PurchaseOrderApprovalRuleConditionQuantity';
    }
}
