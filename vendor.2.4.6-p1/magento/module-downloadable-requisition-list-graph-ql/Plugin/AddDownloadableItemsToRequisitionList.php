<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DownloadableRequisitionListGraphQl\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Downloadable\Model\Product\Type;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;

class AddDownloadableItemsToRequisitionList
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
         * loads the additional options for downloadable products
         */
        if ($product->getTypeId() == Type::TYPE_DOWNLOADABLE && !empty($options['info_buyRequest'])) {
            $infoBuyRequest = $options['info_buyRequest'];
            if (!empty($infoBuyRequest['links'])) {
                $options['links'] = $infoBuyRequest['links'];
            }
        }
        return [$options, $product, $qty];
    }
}
