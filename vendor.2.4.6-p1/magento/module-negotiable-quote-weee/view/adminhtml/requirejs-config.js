/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            'Magento_NegotiableQuote/template/quote/table-row.html':
                'Magento_NegotiableQuoteWeee/template/quote/table-row.html'
        }
    },
    config: {
        mixins: {
            'Magento_NegotiableQuote/quote/create/negotiation-totals': {
                'Magento_NegotiableQuoteWeee/js/quote/create/negotiation-totals-mixin': true
            }
        }
    }
};
