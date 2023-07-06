/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiClass',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'mage/validation'
], function ($, Class, customerData, $t) {
    'use strict';

    return Class.extend({
        defaults: {
            valueChanged: false,
            initialValue: null,
            ruleIdSelector: null,
            ruleId: null,
            inputElement: null,
            validationURL: null
        },

        /** @inheritdoc */
        initialize: function (config, element) {
            this._super();
            this.ruleId = $(this.ruleIdSelector).val();
            this.initialValue = element.value;
            this.inputElement = element;
            this.initEvents();
            this.initValidationRule();

            return this;
        },

        /**
         * Initialize events
         */
        initEvents: function () {
            $(this.inputElement).on('blur', this.validateName.bind(this));
            $(this.inputElement).on('change', this.onChange.bind(this));
        },

        /**
         * Add validation rule for unique rule name.
         */
        initValidationRule: function () {
            $.validator.addMethod('validate-unique-name', function () {
                return false;
            }, $.mage.__('This rule name already exists. Enter a unique rule name.'));
        },

        /**
         * On value change handler.
         */
        onChange: function () {
            this.valueChanged = true;
        },

        /**
         * Validate rule name.
         */
        validateName: function () {
            var value = this.inputElement.value,
                trimedValue = value.trim();

            if (!trimedValue || !this.valueChanged) {
                return;
            } else if (this.initialValue === value) {
                this.toggleValidationRule(false);

                return;
            }

            $.ajax({
                url: this.validationURL,
                data: {
                    'rule_name': value,
                    'rule_id': this.ruleId
                },
                showLoader: true
            }).done(function (response) {
                this.valueChanged = false;
                this.toggleValidationRule(!response.isValid);
            }.bind(this)).fail(function () {
                customerData.set('messages', {
                    messages: [{
                        type: 'error',
                        text: $t('An error occurred while validating the rule name. Please try again.')
                    }],
                    'data_id': Math.floor(Date.now() / 1000)
                });
            });
        },

        /**
         * Toggle unique name validation rule.
         *
         * @param {Boolean} show
         */
        toggleValidationRule: function (show) {
            if (show) {
                $(this.inputElement).addClass('validate-unique-name');
            } else {
                $(this.inputElement).removeClass('validate-unique-name');
            }

            $.validator.validateSingleElement(this.inputElement);
        }
    });
});
