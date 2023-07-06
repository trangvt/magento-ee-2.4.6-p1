<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardRequisitionListGraphQl\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;

class AddGiftCardItemsToRequisitionList
{

    /**
     * @param AddItemsToRequisitionList $subject
     * @param array $options
     * @param ProductInterface $product
     * @param float $qty
     * @return array
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function beforePrepareOptions(AddItemsToRequisitionList $subject, array $options, ProductInterface $product, float $qty)
    {
        if ($product->getTypeId() === Giftcard::TYPE_GIFTCARD && !empty($options['info_buyRequest'])) {
            /**
             * loads the additional options for giftcard products
             */
            $infoBuyRequest = $options['info_buyRequest'];
            if (!empty($infoBuyRequest['giftcard_sender_email']) &&
                !empty($infoBuyRequest['giftcard_sender_name']) &&
                !empty($infoBuyRequest['giftcard_recipient_name']) &&
                !empty($infoBuyRequest['giftcard_recipient_email'])
            ) {
                $options['giftcard_sender_name'] = $infoBuyRequest['giftcard_sender_name'];
                $options['giftcard_sender_email'] = $infoBuyRequest['giftcard_sender_email'];
                $options['giftcard_recipient_name'] = $infoBuyRequest['giftcard_recipient_name'];
                $options['giftcard_recipient_email'] = $infoBuyRequest['giftcard_recipient_email'];
                $options['giftcard_message'] = $infoBuyRequest['giftcard_message'];
            } else {
                throw new GraphQlInputException(__("Please add required gift card fields"));
            }

            if (isset($infoBuyRequest['giftcard_amount']) && $infoBuyRequest['giftcard_amount'] == "custom") {
                $options['giftcard_amount'] = $infoBuyRequest['giftcard_amount'];
                $options['custom_giftcard_amount'] = $infoBuyRequest['custom_giftcard_amount'];
            } elseif (isset($infoBuyRequest['giftcard_amount'])) {
                $options['giftcard_amount'] = $infoBuyRequest['giftcard_amount'];
            }
        }
        return [$options, $product, $qty];
    }
}
