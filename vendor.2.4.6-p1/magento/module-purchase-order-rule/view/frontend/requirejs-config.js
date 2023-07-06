/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    deps: [
        'Magento_PurchaseOrderRule/js/validation/messages'
    ],
    map: {
        '*': {
            uiPurchaseOrderRulePaging:        'Magento_PurchaseOrderRule/js/grid/paging/paging',
            uiPurchaseOrderRuleListing:       'Magento_PurchaseOrderRule/js/grid/listing',
            uiPurchaseOrderAddNewRuleButton:  'Magento_PurchaseOrderRule/js/grid/add-new-rule-button'
        }
    }
};
