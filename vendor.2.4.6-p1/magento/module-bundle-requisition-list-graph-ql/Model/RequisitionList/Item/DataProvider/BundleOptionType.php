<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleRequisitionListGraphQl\Model\RequisitionList\Item\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\RequisitionList\Model\RequisitionListItem;

/**
 * BundleOption DataProvider
 */
class BundleOptionType
{

    /**
     * Get bundle option data
     *
     * @param Product $product
     * @param RequisitionListItem $item
     * @return array
     * @throws GraphQlInputException
     */
    public function getData(Product $product, RequisitionListItem $item): array
    {
        $optionsCollection = $product->getTypeInstance()->getOptionsCollection($product);
        $optionItems = $optionsCollection->getitems();
        $options = [];
        $values = [];

        foreach ($optionItems as $optionItem) {
            $options[]  = [
                'id' => $optionItem->getId(),
                'label' => $optionItem->getId(),
                'type' => $optionItem->getType(),
                'values' => [],
            ];
        }

        $optionsId = [];
        $ids = [];
        foreach ($options as $option) {
            array_push($optionsId, $option['id']);

            $buyRequest = array_key_exists('info_buyRequest', $item->getOptions()) ?
                $item->getOptions()['info_buyRequest'] : [];

            $bundleOption = array_key_exists('bundle_option', $buyRequest) ? $buyRequest['bundle_option'] : [];

            if (!array_key_exists($option['id'], $bundleOption)) {
                throw new GraphQlInputException(__("No such option with ID %1", $option['id']));
            }
            array_push($ids, $bundleOption[$option['id']]);
        }

        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );
        $selectionArray = [];

        foreach ($selectionCollection as $productSelection) {
            if (in_array($productSelection->getSelectionId(), $ids)) {
                $selectionArray['selection_product_name'] = $productSelection->getName();
                $selectionArray['selection_product_quantity'] = $productSelection->getSelectionQty();
                $selectionArray['selection_product_price'] = $productSelection->getPrice();
                $selectionArray['selection_product_id'] = $productSelection->getProductId();
                $selectionArray['selection_selection_id'] = $productSelection->getSelectionId();
            }

            $values[] = [
                'id' => $selectionArray['selection_selection_id'],
                'label' => $selectionArray['selection_product_name'],
                'quantity' => (int)$selectionArray['selection_product_quantity'],
                'price' => (int)$selectionArray['selection_product_price'],
            ];
        }

        $optionsCount = count($options);
        for ($i = 0; $i < $optionsCount; $i++) {
            $options[$i]['values'][] = $values[$i];
        }

        return $options;
    }
}
