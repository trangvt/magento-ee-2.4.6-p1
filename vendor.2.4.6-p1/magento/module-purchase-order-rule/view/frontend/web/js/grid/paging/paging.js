/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_B2b/js/grid/paging/paging'
], function (gridPaging) {
    'use strict';

    return gridPaging.extend({
        /**
         * Returns path to the template
         * defined for a current display mode.
         *
         * @returns {String} Path to the template.
         */
        getTemplate: function () {
            if (this.totalRecords > 0) {
                return this._super();
            }

            return 'Magento_PurchaseOrderRule/grid/paging/paging-empty';
        }
    });
});
