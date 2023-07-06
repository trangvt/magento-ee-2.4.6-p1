/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_QuickOrder/js/item-table/mass-add-rows',
    'productSkuItem'
], function ($, massAddRows) {
    'use strict';

    $.widget('mage.quickOrderMultipleSkus', {
        options: {
            textArea: '[data-role="multiple-skus"]',
            newBlock: '[data-role="new-block"]',
            skuSelector: '[data-role="product-sku"]',
            qtySelector: '[data-role="product-qty"]',
            urlSku: '',
            showError: '[data-role="show-errors"]',
            dataError: {
                text: null
            },
            errorType: 'multiple'
        },

        /**
         * This method constructs a new widget
         *
         * @private
         */
        _create: function () {
            this._bind();
        },

        /**
         * This method binds elements in this widget
         *
         * @private
         */
        _bind: function () {
            var $textArea = $(this.options.textArea);

            this.element.on('click', $.proxy(this._moveSkusToSingleInputs, this));
            $textArea.on('keyup', $.proxy(this._setDisableAttribute, this, $textArea));
            this._setDisableAttribute($textArea);
        },

        /**
         * Enable or disable button based on input's length.
         *
         * @param {jQuery} $textArea
         * @private
         */
        _setDisableAttribute: function ($textArea) {
            var hasContent = $textArea.val().trim().length;

            this._disableAddToListButton(!hasContent);
        },

        /**
         * Set sku names and qty in fields
         *
         * @private
         */
        _moveSkusToSingleInputs: function () {
            var postArray = [],
                self = this,
                skuArray = this._getValueArray(),
                skuCounter = 0;

            $.each(skuArray, function (index, value) {
                var val = value.trim(),
                    singleSkuInput = self._getSingleSkuInput(val, true),
                    item = {
                        'sku': val,
                        'qty': 1
                    },
                    skipItem = false;

                postArray.filter(function (postItem) {
                    if (postItem.sku.toLowerCase() === val) {
                        ++postItem.qty;
                        skipItem = true;
                    }
                });

                if (singleSkuInput) {
                    item.qty = singleSkuInput.qty;
                }

                if (!singleSkuInput && skuArray.indexOf(val) === index) { //eslint-disable-line eqeqeq
                    skuCounter++;
                }

                if (!skipItem) {
                    postArray.push(item);
                }
            });

            $('body').trigger('processStart');
            $.when($.post(
                this.options.urlSku, {
                    'items': JSON.stringify(postArray),
                    'errorType': this.options.errorType
                }
            ), massAddRows.addNewRows($(this.options.newBlock + ':first'), skuCounter)).done(function (result) {
                var data = result[0];

                self.options.dataError.text = null;

                $.each(data.items, function (index, item) {
                    var singleSkuInput = self._getSingleSkuInput(item.sku.toString());

                    if (singleSkuInput !== false) {
                        item.toRewriteQty = true;
                        singleSkuInput.trigger('addRow', item);
                    }
                });

                if (data && data.generalErrorMessage && data.generalErrorMessage !== '') {
                    self.options.dataError.text = data.generalErrorMessage;
                }

                $(self.options.showError).trigger('addErrors', {
                    text: self.options.dataError.text
                });
                self._clearInput();
                $('body').trigger('processStop');
            });
        },

        /**
         * Clear multiple SKU input
         *
         * @private
         */
        _clearInput: function () {
            $(this.options.textArea).val('');
            this._disableAddToListButton(true);
        },

        /**
         * Change state of Add to List button.
         *
         * @param {Boolean} disable
         * @private
         */
        _disableAddToListButton: function (disable) {
            this.element.prop('disabled', disable);
        },

        /**
         * Get all sku names
         *
         * @returns {Array} sku names
         * @private
         */
        _getValueArray: function () {
            return $(this.options.textArea).val().toLowerCase().split(/,|\n/);
        },

        /**
         * Get first empty field
         *
         * @param {String} sku
         * @param {Boolean} skipEmpty
         * @returns {Boolean|Object} get false if we need skip and value empty
         * @private
         */
        _getSingleSkuInput: function (sku, skipEmpty) {
            var allSkuInputs = $(this.options.skuSelector),
                self = this,
                elem = false;

            $.each(allSkuInputs, function () {
                if ($(this).val() === '' && !skipEmpty || $(this).val().toLowerCase() === sku) {
                    elem = $(this);
                    elem.qty = parseFloat(elem.closest('.deletable-item').find(self.options.qtySelector)[0].value) + 1;

                    return false;
                }
            });

            return elem;
        }
    });

    return $.mage.quickOrderMultipleSkus;
});
