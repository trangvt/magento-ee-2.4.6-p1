/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var availableShippingMethodsField = $(config.availableShippingMethodsSelector),
            enableCompanyField = $(config.enableCompanyFieldSelector),
            applicableShippingMethodsField = $(element);

        /**
         * Change Available Shipping Methods field
         *
         * @param {Boolean} flag
         * @private
         */
        function _disableAvailableShippingMethodsField(flag) {
            availableShippingMethodsField.prop('disabled', flag);
        }

        if (applicableShippingMethodsField.val() === '0') {
            _disableAvailableShippingMethodsField(true);
            availableShippingMethodsField.find('option').prop('selected', true);
        }

        applicableShippingMethodsField.change(function () {
            if (applicableShippingMethodsField.val() === '0') {
                _disableAvailableShippingMethodsField(true);
            } else {
                _disableAvailableShippingMethodsField(false);
            }
        });

        enableCompanyField.change(function () {
            if (enableCompanyField.val() === '1' && applicableShippingMethodsField.val() === '0') {
                _disableAvailableShippingMethodsField(true);
            }
        });
    };
});
