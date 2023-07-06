/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'uiElement',
    'underscore'
], function (Element, _) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Magento_PurchaseOrderRule/grid/add-new-rule-button',
            isCreateRuleAllowed: false,
            title: '',
            containerClasses: {},
            buttonClasses: '',
            imports: {
                isCreateRuleAllowed: '${ $.provider }:data.isCreateRuleAllowed',
                createRuleUrl: '${ $.provider }:data.createRuleUrl',
                totalRecords: '${ $.provider }:data.totalRecords'
            }
        },

        /**
         * Calls 'initObservable' of parent
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super()
                .observe(['title'])
                .track([
                    'isCreateRuleAllowed',
                    'createRuleUrl',
                    'totalRecords'
                ]);

            return this;
        },

        /**
         * Set container classes
         *
         * @returns {Object} Classes
         */
        setContainerClasses: function () {
            var classes;

            if (_.isString(this.containerClasses)) {
                classes = this.containerClasses.split(' ');
                this.containerClasses = {};
                classes.forEach(function (name) {
                    this.containerClasses[name] = true;
                }.bind(this));
            }

            if (!this.containerClasses) {
                this.containerClasses = {};
            }

            _.extend(this.containerClasses, {
                'empty-rules': this.totalRecords === 0
            });

            return this.containerClasses;
        }
    });
});
