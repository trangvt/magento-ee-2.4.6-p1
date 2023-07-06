<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ConfigurableNegotiableQuote\Model;

use Magento\NegotiableQuote\Model\ProductOptionsProviderInterface;

/**
 * Responsible for retrieving configurable product options.
 */
class ProductOptionsProvider implements ProductOptionsProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getProductType()
    {
        return \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(\Magento\Catalog\Model\Product $product)
    {
        return $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
    }
}
