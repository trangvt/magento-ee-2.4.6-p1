/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_PurchaseOrder/js/action/place-po-order': {
                'Magento_CheckoutAgreementsPurchaseOrder/js/set-payment-information-mixin': true
            }
        }
    }
};
