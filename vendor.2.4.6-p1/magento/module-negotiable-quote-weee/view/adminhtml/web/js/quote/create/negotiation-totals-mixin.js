/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var mixin = {
        /**
         * Adds totals for weee data
         */
        options: {
            subtotalWeee: '.catalog_weee td',
            quoteWeee: '.quote_weee td'
        },

        /**
         * Adds weee total elements to elements object.
         */
        _setElements: function () {
            this._super();

            this.options.elements.subtotalWeee = $(this.options.subtotalWeee);
            this.options.elements.quoteWeee = $(this.options.quoteWeee);
        }
    };

    /**
     * Override default negotiationTotals.
     */
    return function (targetWidget) {
        $.widget('mage.negotiationTotals', targetWidget, mixin);

        return $.mage.negotiationTotals;
    };
});
