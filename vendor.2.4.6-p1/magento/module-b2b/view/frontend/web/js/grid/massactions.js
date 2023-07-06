/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'underscore',
    'uiRegistry',
    'mageUtils',
    'Magento_Ui/js/grid/massactions',
    'Magento_Ui/js/modal/confirm',
    'Magento_Customer/js/customer-data',
    'mage/cookies'
], function ($, _, registry, utils, Massactions, confirm, customerData) {
    'use strict';

    return Massactions.extend({
        defaults: {
            templateTmp: '',
            imports: {
                tabName: '${ $.provider }:data.tabName'
            },
            listens: {
                tabName: 'onTabNameUpdated'
            },
            tracks: {
                template: true
            }
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.templateTmp = this.template;
            this.template = '';
            window.FORM_KEY = $.mage.cookies.get('form_key');
        },

        /**
         * Hides massaction toolbar if not allowed
         *
         * @returns void
         */
        onTabNameUpdated: function () {
            if (this.isCompanyAdmin()) {
                this.template = this.templateTmp;
            }

            if (!this.isCompanyAdmin() && this.tabName === 'require_my_approval') {
                this.template = this.templateTmp;
            }
        },

        /**
         * Retrieves number of selected orders.
         *
         * @returns {Number}
         */
        selectedItemsNumber: function () {
            if (this.selections().totalSelected() === undefined) {
                return 0;
            }

            return this.selections().totalSelected();
        },

        /**
         * Retrieves status of approval button.
         *
         * @returns {Boolean}
         */
        isEnabled: function () {
            return this.selectedItemsNumber() !== 0;
        },

        /** @inheritdoc */
        _confirm: function (action, callback) {
            var confirmData = action.confirm,
                confirmMessage = confirmData.message;

            confirm({
                title: confirmData.title,
                content: confirmMessage,
                actions: {
                    confirm: callback
                }
            });
        },

        /**
         * Check if customer is company admin.
         *
         * @returns {Boolean}
         */
        isCompanyAdmin: function () {
            var companyData = customerData.get('company');

            return companyData()['is_company_admin'];
        }
    });
});
