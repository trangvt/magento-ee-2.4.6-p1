/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/single-checkbox'
], function (_, checkbox) {
    'use strict';

    return checkbox.extend({
        defaults: {
            modules: {
                applicableShippingsField: '${ $.applicableShippingsFieldName }',
                availableShippingsField: '${ $.availableShippingsFieldName }'
            },
            applicableShippingMethods: {
                allEnabled: '',
                selected: ''
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            _.defer(this._checkStatus.bind(this));
        },

        /**
         * @inheritdoc
         */
        _checkStatus: function () {
            if (parseInt(this.value(), 10)) {
                this.disableDependencies();
            }
        },

        /**
         * @inheritdoc
         */
        disableDependencies: function () {
            this.applicableShippingsField().value(0);
            this.applicableShippingsField().disable();

            if (this.availableShippingsField()) {
                this.availableShippingsField().disable();
            }
        },

        /**
         * @inheritdoc
         */
        onCheckedChanged: function (newChecked) {
            this._super();

            if (!this.applicableShippingsField()) {
                return;
            }

            if (!newChecked) {
                this.applicableShippingsField().enable();

                if (this.applicableShippingsField().value() === this.applicableShippingMethods.selected) {
                    this.availableShippingsField().enable();
                }
            } else {
                this.disableDependencies();
            }
        }
    });
});
