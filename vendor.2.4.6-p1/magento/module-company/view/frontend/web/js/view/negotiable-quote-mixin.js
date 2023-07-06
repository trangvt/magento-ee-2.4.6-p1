/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Company/js/authorization'
], function (authorization) {
    'use strict';

    var mixin = {

        /**
         * Is sales view orders allowed.
         *
         * @returns {Boolean}
         */
        isSalesAllAllowed: function () {
            return authorization.isAllowed('Magento_Sales::view_orders');
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
