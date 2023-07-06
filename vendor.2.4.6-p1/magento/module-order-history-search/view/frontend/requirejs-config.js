/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            myOrdersFilter: 'Magento_OrderHistorySearch/js/order/filter'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'Magento_OrderHistorySearch/js/validation': true
            }
        }
    }
};
