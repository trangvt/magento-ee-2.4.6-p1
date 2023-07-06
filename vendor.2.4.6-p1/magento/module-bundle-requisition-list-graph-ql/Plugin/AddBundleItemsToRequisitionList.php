<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleRequisitionListGraphQl\Plugin;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;

class AddBundleItemsToRequisitionList
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
        /**
         * loads the additional options for bundled products
         */
        if ($product->getTypeId() === Type::TYPE_CODE && !empty($options['info_buyRequest'])) {
            $infoBuyRequest = $options['info_buyRequest'];
            if (!empty($infoBuyRequest['bundle_option']) &&
                !empty($infoBuyRequest['bundle_option_qty'])) {
                $options['bundle_option'] = $infoBuyRequest['bundle_option'];
                $options['bundle_option_qty'] = $infoBuyRequest['bundle_option_qty'];
            }
        }
        return [$options, $product, $qty];
    }
}
