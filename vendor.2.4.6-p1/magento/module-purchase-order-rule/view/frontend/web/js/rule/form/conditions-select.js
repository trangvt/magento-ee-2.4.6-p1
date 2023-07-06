/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.purchaseOrderRuleCondition', {
        options: {
            conditionPrefix: '#rule-condition-',
            conditionFieldsetSelector: '.fieldset.condition',
            approversFieldsetSelector: '.fieldset.rule-approvers'
        },

        /** @inheritdoc */
        _create: function () {
            var $conditionFieldsets = $(this.options.conditionFieldsetSelector),
                condtionPrefix = this.options.conditionPrefix,
                $approversFieldset = $(this.options.approversFieldsetSelector);

            this.element.on('change', function () {
                $conditionFieldsets.each(function (i, el) {
                    $(el).addClass('_hide');
                });
                $conditionFieldsets.find('[name]').each(function (i, el) {
                    $(el).attr('data-name', $(el).attr('name'));
                    $(el).removeAttr('name');
                });
                $(condtionPrefix + this.value).removeClass('_hide');
                $(condtionPrefix + this.value).find('[data-name]').attr('name', function () {
                    return $(this).attr('data-name');
                });

                if (this.value) {
                    $($approversFieldset).removeClass('_hide');
                } else {
                    $($approversFieldset).addClass('_hide');
                }
            });
        }
    });

    return $.mage.purchaseOrderRuleCondition;
});
