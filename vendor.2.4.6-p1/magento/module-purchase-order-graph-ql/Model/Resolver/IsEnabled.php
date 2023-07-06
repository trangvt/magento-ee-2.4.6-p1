<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderGraphQl\Model\Resolver;

use Magento\CompanyGraphQl\Model\Company\ResolverAccess;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;

/**
 * Purchase Order Resolver IsEnabled
 */
class IsEnabled implements ResolverInterface
{
    /**
     * @var PurchaseOrderConfig
     */
    private PurchaseOrderConfig $purchaseOrderConfig;

    /**
     * @var ResolverAccess
     */
    private ResolverAccess $resolverAccess;

    /**
     * @var array
     */
    private array $allowedResources;

    /**
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param ResolverAccess $resolverAccess
     * @param array $allowedResources
     */
    public function __construct(
        PurchaseOrderConfig $purchaseOrderConfig,
        ResolverAccess $resolverAccess,
        array $allowedResources = []
    ) {
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->resolverAccess = $resolverAccess;
        $this->allowedResources = $allowedResources;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->resolverAccess->isAllowed($this->allowedResources);
        return $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite();
    }
}
