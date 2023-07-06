<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableRequisitionListGraphQl\Model\RequisitionList\Item\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\RequisitionList\Model\RequisitionListItem;

class ConfigurableOptionType
{
    /**
     * Get custom options data
     *
     * @param RequisitionListItem $item
     * @param Product $product
     * @return array
     */
    public function getCustomOptions(RequisitionListItem $item, Product $product)
    {
        $configurableOptions = $product->getTypeInstance()->getConfigurableOptions($product);

        $buyRequest = array_key_exists('info_buyRequest', $item->getOptions()) ?
            $item->getOptions()['info_buyRequest'] : [];

        $superAttribute = array_key_exists('super_attribute', $buyRequest) ? $buyRequest['super_attribute'] : [];

        $options = [];
        foreach ($configurableOptions as $attribute) {
            foreach ($attribute as $option) {
                if (in_array($option['value_index'], $superAttribute)) {
                    $optionId = array_keys($superAttribute, $option['value_index']);
                    unset($superAttribute[$optionId[0]]);
                    $options[] = [
                        'label' => $option['super_attribute_label'],
                        'value' => $option['default_title'],
                        'print_value' => $option['option_title'],
                        'option_id' => $optionId[0],
                        'option_value' => $option['value_index'],
                    ];
                }
            }
        }

        return $options;
    }
}
