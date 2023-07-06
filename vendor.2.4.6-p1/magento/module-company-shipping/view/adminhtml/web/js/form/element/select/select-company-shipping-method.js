/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/select'
], function (_, SelectElement) {
    'use strict';

    return SelectElement.extend({
        defaults: {
            modules: {
                availableShippingsField: '${ $.availableShippingsFieldName }'
            },
            applicableShippingMethods: {
                b2b: '',
                allEnabled: ''
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            _.defer(this._selectOptions.bind(this));
        },

        /**
         * @inheritdoc
         */
        onUpdate: function () {
            this._super();
            this._selectOptions();
        },

        /**
         * Select options
         *
         * @private
         */
        _selectOptions: function () {
            if (this.value() === this.applicableShippingMethods.b2b) {
                this._disableShippingMethodsField();

                if (this.availableShippingsField()) {
                    this.availableShippingsField().value(this._getSelectedShippingMethods());
                }
            } else if (this.value() === this.applicableShippingMethods.allEnabled) {
                this._disableShippingMethodsField();
                this.availableShippingsField().value(this._getInitialOptions());
            } else if (!this.disabled()) {
                this.availableShippingsField().enable();
            }
        },

        /**
         * Disable shipping methods field
         *
         * @private
         */
        _disableShippingMethodsField: function () {
            if (this.availableShippingsField() && !this.availableShippingsField().disabled()) {
                this.availableShippingsField().disable();
            }
        },

        /**
         * Get initial shipping methods
         *
         * @returns {Array}
         * @private
         */
        _getInitialOptions: function () {
            var options = [],
                initialOptions = this.availableShippingsField().initialOptions,
                i;

            for (i = 0; i < initialOptions.length; i++) {
                options[i] = initialOptions[i].value;
            }

            return options;
        },

        /**
         * Get selected shipping methods
         *
         * @returns {Array}
         * @private
         */
        _getSelectedShippingMethods: function () {
            var selectedMethods = [];

            if (this.b2bShippingMethods) {
                selectedMethods = this.b2bShippingMethods.split(',');
            }

            return selectedMethods;
        }
    });
});
