<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuoteGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\ProductDataProvider;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for products removed from a negotiable quote
 */
class QuoteProducts implements ResolverInterface
{
    /**
     * @var ProductDataProvider
     */
    private $productDataProvider;

    /**
     * @param ProductDataProvider $productDataProvider
     */
    public function __construct(ProductDataProvider $productDataProvider)
    {
        $this->productDataProvider = $productDataProvider;
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
        $products = [];
        if (empty($value['products_removed_from_quote'])) {
            return $products;
        }
        foreach ($value['products_removed_from_quote'] as $productId) {
            $products[] = $this->productDataProvider->getProductDataById((int)$productId);
        }
        return $products;
    }
}
