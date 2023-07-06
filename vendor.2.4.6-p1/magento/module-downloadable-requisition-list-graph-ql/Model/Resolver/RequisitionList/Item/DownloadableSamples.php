<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableRequisitionListGraphQl\Model\Resolver\RequisitionList\Item;

use Magento\Catalog\Model\Product;
use Magento\DownloadableGraphQl\Model\ConvertSamplesToArray;
use Magento\DownloadableGraphQl\Model\GetDownloadableProductSamples;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class DownloadableSamples implements ResolverInterface
{
    /**
     * @var GetDownloadableProductSamples
     */
    private $getDownloadableProductSamples;

    /**
     * @var ConvertSamplesToArray
     */
    private $convertSamplesToArray;

    /**
     * Samples constructor
     *
     * @param GetDownloadableProductSamples $getDownloadableProductSamples
     * @param ConvertSamplesToArray $convertSamplesToArray
     */
    public function __construct(
        GetDownloadableProductSamples $getDownloadableProductSamples,
        ConvertSamplesToArray $convertSamplesToArray
    ) {
        $this->getDownloadableProductSamples = $getDownloadableProductSamples;
        $this->convertSamplesToArray = $convertSamplesToArray;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['product']['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['product']['model'];

        $samples = $this->getDownloadableProductSamples->execute($product);

        return $this->convertSamplesToArray->execute($samples);
    }
}
