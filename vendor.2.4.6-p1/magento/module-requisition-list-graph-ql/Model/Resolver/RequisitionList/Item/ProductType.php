<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Product type Resolver GraphQl
 */
class ProductType implements TypeResolverInterface
{
    /**
     * @var array
     */
    private $supportedTypes;

    /**
     * ProductTypeResolver constructor
     *
     * @param array $supportedTypes
     */
    public function __construct(array $supportedTypes = [])
    {
        $this->supportedTypes = $supportedTypes;
    }

    /**
     * Determine a concrete GraphQL type based off the given data.
     *
     * @param array $data
     * @return string
     * @throws GraphQlInputException
     */
    public function resolveType(array $data): string
    {
        if (!isset($data['product'])) {
            throw new GraphQlInputException(__('Missing key "product" in requisition data'));
        }
        $productData = $data['product'];

        if (!isset($productData['type_id'])) {
            throw new GraphQlInputException(__('Missing key "type_id" in product data'));
        }
        $productTypeId = $productData['type_id'];

        if (!isset($this->supportedTypes[$productTypeId])) {
            throw new GraphQlInputException(
                __('Product "%product_type" type is not supported', ['product_type' => $productTypeId])
            );
        }
        return $this->supportedTypes[$productTypeId];
    }
}
