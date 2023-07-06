/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/grid/listing'
], function (customerData, gridListing) {
    'use strict';

    return gridListing.extend({
        defaults: {
            template: 'Magento_B2b/grid/listing',
            selectableStatuses: []
        },

        /**
         * @return {*}
         */
        getTableClass: function () {
            return this['table_css_class'];
        },

        /**
         * Check if row is disabled for edit.
         *
         * @param {Object} row
         * @return {Boolean}
         */
        isRowEditDisabled: function (row) {
            if (this.isCompanyAdmin()) {
                return !this.selectableStatuses.hasOwnProperty(row.status);
            }

            if (this.source.data.tabName === 'require_my_approval' &&
                (!this.selectableStatuses.hasOwnProperty(row.status) ||
                    row.approvedByMe !== undefined && row.approvedByMe
                )
            ) {
                return true;
            }
        },

        /**
         * Check if bulk actions allowed.
         *
         * @returns {Boolean}
         */
        isCompanyAdmin: function () {
            var companyData = customerData.get('company');

            return companyData()['is_company_admin'];
        }
    });
});
