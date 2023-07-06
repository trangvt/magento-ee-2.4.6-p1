/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'ko',
    'mageUtils',
    'Magento_Ui/js/grid/filters/filters'
], function (_, ko, utils, gridFilters) {
    'use strict';

    /**
     * Removes empty properties from the provided object.
     *
     * @param {Object} data - Object to be processed.
     * @returns {Object}
     */
    function removeEmpty(data) {
        var result = utils.mapRecursive(data, utils.removeEmptyValues.bind(utils));

        return utils.mapRecursive(result, function (value) {
            return _.isString(value) ? value.trim() : value;
        });
    }

    return gridFilters.extend({
        defaults: {
            template: 'Magento_Company/users/grid/filters/filters',
            showAllUsers: ko.observable(false),
            showActiveUsers: ko.observable(true)
        },

        /**
         * Sets filter for status field to 'active'.
         */
        setStatusActive: function () {
            this.showAllUsers(false);
            this.showActiveUsers(true);
            this.filters.status = this.params.statusActive;
            this.apply();
        },

        /**
         * Sets filter for status field to 'inactive'.
         */
        setStatusInactive: function () {
            this.showAllUsers(false);
            this.showActiveUsers(false);
            this.filters.status = this.params.statusInactive;
            this.apply();
        },

        /**
         * Clears filters data.
         */
        clear: function () {
            this.showAllUsers(true);
            this._super(null);
        },

        /**
         * Sets filters data to the applied state.
         *
         * @returns {Filters} Chainable.
         */
        apply: function () {
            this.set('applied', removeEmpty(this.filters));

            return this;
        }
    });
});
