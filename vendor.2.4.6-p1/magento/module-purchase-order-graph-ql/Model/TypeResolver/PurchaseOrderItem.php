<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\TypeResolver;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolves PurchaseOrderItem type
 */
class PurchaseOrderItem implements TypeResolverInterface
{
    /**
     * User Errors
     */
    private const TYPE = 'PurchaseOrderItem';

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        return self::TYPE;
    }
}
