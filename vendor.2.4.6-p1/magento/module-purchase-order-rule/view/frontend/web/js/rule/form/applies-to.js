/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.purchaseOrderRuleAppliesTo', {
        options: {
            radioSelector: '[name=applies_to_all]',
            specificWrapperSelector: '.applies-to-select',
            specificSelectSelector: '.applies-to-select select',
            uiGroupValueInputSelector: 'input[name="applies_to\[\]"]'
        },

        /** @inheritdoc */
        _create: function () {
            var radioElement = this.element.find(this.options.radioSelector),
                wrapperElement = this.element.find(this.options.specificWrapperSelector);

            radioElement.on('change', function (element) {
                var appliesToInputs = this.element.find(this.options.uiGroupValueInputSelector);

                if (element.target.value === '0') {
                    wrapperElement.addClass('show');
                    appliesToInputs.prop('disabled', false);
                } else {
                    wrapperElement.removeClass('show');
                    appliesToInputs.prop('disabled', true);
                }
            }.bind(this));
        }
    });

    return $.mage.purchaseOrderRuleAppliesTo;
});
