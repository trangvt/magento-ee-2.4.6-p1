<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardRequisitionListGraphQl\Model\RequisitionList\Item\DataProvider;

use Magento\GraphQl\Model\Query\Context;
use Magento\RequisitionList\Model\RequisitionListItem;

class GiftCardOptionType
{
    /**
     * Get giftcard options data
     *
     * @param RequisitionListItem $item
     * @param Context $context
     * @return array
     */
    public function getProductData(RequisitionListItem $item, Context $context)
    {
        $buyRequest = array_key_exists('info_buyRequest', $item->getOptions()) ?
        $item->getOptions()['info_buyRequest'] : [];
        $giftCardOptions = [];
        $giftCardOptions['sender_name'] = $buyRequest['giftcard_sender_name'] ?? '';
        $giftCardOptions['sender_email'] = $buyRequest['giftcard_sender_email'] ?? '';
        $giftCardOptions['recipient_name'] = $buyRequest['giftcard_recipient_name'] ?? '';
        $giftCardOptions['recipient_email'] = $buyRequest['giftcard_recipient_email'] ?? '';
        if ($buyRequest['giftcard_amount'] != "custom") {
            $giftCardOptions['amount'] = [
                'value' => floatval($buyRequest['giftcard_amount']),
                'currency' => $context->getExtensionAttributes()->getStore()->getCurrentCurrencyCode(),
            ];
        } else {
            $giftCardOptions['custom_giftcard_amount'] = [
                'value' => floatval($buyRequest['custom_giftcard_amount']),
                'currency' => $context->getExtensionAttributes()->getStore()->getCurrentCurrencyCode(),
            ];
        }

        $giftCardOptions['message'] = $buyRequest['giftcard_message'] ?? '';

        return $giftCardOptions;
    }
}
