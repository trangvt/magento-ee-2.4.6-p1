/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/grid/columns/multiselect'
], function (_, customerData, Multiselect) {
    'use strict';

    return Multiselect.extend({
        defaults: {
            selectableStatuses: [],
            imports: {
                totalFilteredRecords: '${ $.provider }:data.totalFilteredRecords',
                tabName: '${ $.provider }:data.tabName'
            },
            listens: {
                tabName: 'onTabNameUpdated'
            }
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.visible = false;

            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'totalFilteredRecords',
                    'tabName'
                ]);

            return this;
        },

        /**
         * Hides multiselect column if not allowed
         *
         * @returns void
         */
        onTabNameUpdated: function () {
            this.visible = !(!this.isCompanyAdmin() && this.tabName() === 'company');
        },

        /** @inheritdoc */
        onRowsChange: function () {
            this.updateDisabled();
            this._super();
        },

        /** @inheritdoc */
        updateExcluded: function (selected) {
            var excluded;

            this._super(selected);
            excluded = this.excluded();
            excluded = _.union(excluded, this.disabled());
            this.excluded(excluded);

            return this;
        },

        /** @inheritdoc */
        onSelectedChange: function () {
            this._super();
            this.updateDisabled();
        },

        /**
         * Handles changes of disabled items.
         */
        updateDisabled: function () {
            var disabledFromPage,
                excluded,
                disabled;

            if (this.rows().length) {
                disabledFromPage = this.getDisabledFromPage();
                excluded = this.excluded();
                disabled = this.disabled();
                excluded = _.union(excluded, disabledFromPage);
                this.excluded(excluded);
                disabled = _.union(disabled, disabledFromPage);
                this.disabled(disabled);
            }
        },

        /**
         * Calculates number of disabled items on the current page.
         */
        getDisabledFromPage: function () {
            var fromPage = [],
                self = this,
                disabledEntry;

            this.rows().forEach(function (entry) {
                disabledEntry = !self.selectableStatuses.hasOwnProperty(entry.status);

                if (self.source().data.tabName === 'require_my_approval' && entry.approvedByMe === true) {
                    disabledEntry = true;
                }

                if (disabledEntry) {
                    fromPage.push(entry['entity_id']);
                }
            });

            return fromPage;

        },

        /** @inheritdoc */
        countSelected: function () {
            var total = this.totalFilteredRecords(),
                excluded = this.excluded().length,
                selected = this.selected().length,
                disabled = this.disabled().length;

            if (this.excludeMode()) {
                selected = total - excluded + disabled;
            }
            this.totalSelected(selected);

            return this;
        },

        /** @inheritdoc */
        getSelections: function () {
            var selections = this._super();

            selections.params.hasDisabled = !!this.disabled().length;

            return selections;
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
