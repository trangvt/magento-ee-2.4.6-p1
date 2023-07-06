/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_NegotiableQuote/js/action/place-order-negotiable-quote': {
                'Magento_CheckoutAgreementsNegotiableQuote/js/action/place-order-negotiable-quote-mixin': true
            }
        }
    }
};
