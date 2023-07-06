/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_B2b/js/grid/listing'
], function (ko, gridListing) {
    'use strict';

    return gridListing.extend({
        defaults: {
            isLoading: true
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Listing} Chainable.
         */
        initObservable: function () {
            this._super().observe([
                'isLoading'
            ]);

            return this;
        },

        /**
         * Returns path to the template
         * defined for a current display mode.
         *
         * @returns {String} Path to the template.
         */
        getTemplate: function () {
            if (this.hasData()) {
                return this._super();
            }

            if (!this.hasData() && !this.isLoading()) {
                return 'Magento_PurchaseOrderRule/grid/listing-empty';
            }

            return 'Magento_PurchaseOrderRule/grid/empty';
        },

        /**
         * Hides loader.
         */
        hideLoader: function () {
            this.isLoading(false);
            this._super();
        },

        /**
         * Shows loader.
         */
        showLoader: function () {
            this.isLoading(true);
            this._super();
        }
    });
});
