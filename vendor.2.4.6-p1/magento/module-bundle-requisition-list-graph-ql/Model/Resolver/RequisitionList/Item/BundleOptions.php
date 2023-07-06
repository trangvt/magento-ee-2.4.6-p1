<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleRequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\BundleRequisitionListGraphQl\Model\RequisitionList\Item\DataProvider\BundleOptionType;

/**
 * Resolver for bundle product options
 */
class BundleOptions implements ResolverInterface
{
    /**
     * @var BundleOptionType
     */
    private $bundleOptionDataProvider;

    /**
     * BundleOptions constructor
     *
     * @param BundleOptionType $bundleOptionDataProvider
     */
    public function __construct(
        BundleOptionType $bundleOptionDataProvider
    ) {
        $this->bundleOptionDataProvider = $bundleOptionDataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['product']['model']) && !isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['product']['model'];

        /** @var RequisitionListItem $item */
        $item = $value['model'];

        return $this->bundleOptionDataProvider->getData($product, $item);
    }
}
