<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrderGraphQl\Model\Resolver\PurchaseOrderActionResolver;

/**
 * Purchase Order Resolver for cancelPurchaseOrders mutation
 */
class Cancel implements ResolverInterface
{
    /**
     * @var PurchaseOrderActionResolver $purchaseOrderActionResolver
     */
    private PurchaseOrderActionResolver $purchaseOrderActionResolver;

    /**
     * @param PurchaseOrderActionResolver $purchaseOrderActionResolver
     */
    public function __construct(
        PurchaseOrderActionResolver $purchaseOrderActionResolver,
    ) {
        $this->purchaseOrderActionResolver = $purchaseOrderActionResolver;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        return $this->purchaseOrderActionResolver->resolve(
            $field,
            $context,
            $info,
            $value,
            ['action' => 'cancel', ...$args]
        );
    }
}
