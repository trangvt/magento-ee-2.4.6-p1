<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableRequisitionListGraphQl\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;

class AddConfigurableItemsToRequisitionList
{

    /**
     * @param AddItemsToRequisitionList $subject
     * @param array $options
     * @param ProductInterface $product
     * @param float $qty
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function beforePrepareOptions(
        AddItemsToRequisitionList $subject,
        array $options,
        ProductInterface $product,
        float $qty
    ) {
        if ($product->getTypeId() === Configurable::TYPE_CODE && !empty($options['info_buyRequest'])) {
            $infoBuyRequest = $options['info_buyRequest'];
            /**
             * loads the additional options for configurable products
             */
            if (!empty($infoBuyRequest['super_attribute'])) {
                $superAttributes = $infoBuyRequest['super_attribute'];
                $options['super_attribute'] = $superAttributes;
                $options['qty'] = $infoBuyRequest['qty'];
            }
        }
        return [$options, $product, $qty];
    }
}
