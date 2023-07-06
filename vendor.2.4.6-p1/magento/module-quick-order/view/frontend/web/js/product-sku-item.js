/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'text!Magento_QuickOrder/templates/product-info.html',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, mageTemplate, infoTpl) {
    'use strict';

    $.widget('mage.productSkuItem', {
        options: {
            urlSku: '',
            urlDelete: '',
            rowIndex: null,
            tableWigetSelector: '',
            addSelector: '[data-role="product-block"]',
            skuSelector: '[data-role="product-sku"]',
            qtySelector: '[data-role="product-qty"]',
            formSelector: '[data-role="send-sku"]',
            showError: '[data-role="show-errors"]',
            removeSelector: '[data-role="delete"]',
            submitBtn: '[data-action="submit-sku"]',
            formSKU: '[data-role="send-sku"]',
            dataError: {
                text: null
            },
            errorType: 'item'
        },

        /**
         * This method constructs a new widget.
         *
         * @private
         */
        _create: function () {
            this._bind();
            this.addBlockTmpl = mageTemplate(infoTpl);
            $(this.options.formSelector).trigger('itemRendered', this);
        },

        /**
         * This method binds elements found in this widget.
         *
         * @private
         */
        _bind: function () {
            var handlers = {};

            handlers['change ' + this.options.skuSelector] = '_reloadItem';
            handlers['change ' + this.options.qtySelector] = '_reloadItem';
            handlers['click ' + this.options.removeSelector] = '_deleteByAjax';
            handlers.addRow = '_addRow';

            this._on(handlers);
        },

        /**
         * Remove old errors and adds new errors.
         *
         * @private
         */
        _reloadError: function () {
            $(this.options.showError).trigger('addErrors', {
                text: this.options.dataError.text
            });

            if (this._isAllRowsEmpty()) {
                $('button.tocart').prop('disabled', true);
            }
        },

        /**
         * This method adds new row for table.
         *
         * @param {Object} e
         * @param {Object} data
         * @private
         */
        _addRow: function (e, data) {
            var skuInput = this.element.find(this.options.skuSelector),
                qtyInput = this.element.find(this.options.qtySelector);

            if (!data) {
                this.element.trigger('addNewRow');

                return false;
            }

            if (skuInput.val() == data.sku) { //eslint-disable-line eqeqeq
                data.qty = parseFloat(data.qty);

                if (!data.toRewriteQty) {
                    data.qty = parseFloat(qtyInput.val()) + parseFloat(data.qty);
                }
            }
            skuInput.val(data.sku);
            qtyInput.val(parseFloat(data.qty));
            this._clearProductBlock();
            this._addBlock(data);

            if (!this._isEmptyRowExist()) {
                this.element.trigger('addNewRow');
            }
        },

        /**
         * Reload item and add new row to end.
         *
         * @private
         */
        _reloadItem: function () {
            this._addByAjax();

            if (!this._isEmptyRowExist()) {
                this._addRow();
            }
        },

        /**
         * Composition data for ajax and sending them.
         *
         * @private
         */
        _addByAjax: function () {
            var postArray = [],
                skuElement = this.element.find(this.options.skuSelector),
                qtyElement = this.element.find(this.options.qtySelector),
                item = {
                    'sku': skuElement.val(),
                    'qty': qtyElement.val()
                },
                self = this,
                isExistedSku = false;

            postArray.push(item);
            this._clearProductBlock();
            qtyElement.prop('readonly', true);

            if ($(this.options.skuSelector).length > 0) {
                $.each($(this.options.skuSelector), function () {
                    if (item.sku && $(this).val().toLowerCase() === item.sku.toString().toLowerCase() &&
                        $(this).attr('id') !== skuElement.attr('id')) {
                        if (item.qty === '') {
                            item.qty = 1;
                        }
                        $(this).closest('.deletable-item').find(self.options.qtySelector).val(
                            parseFloat(
                                $(this).closest('.deletable-item').find(self.options.qtySelector).val()
                            ) + parseFloat(item.qty)
                        );
                        skuElement.val('');
                        qtyElement.val('');
                        isExistedSku = true;
                    }
                });
            }

            if (item.sku !== '' && !isExistedSku) {
                $.post(
                    this.options.urlSku,
                    {
                        'items': JSON.stringify(postArray),
                        'errorType': this.options.errorType
                    },
                    function (data) {
                        this.options.dataError.text = null;
                        $.each(data.items, function (index, it) {
                            this.element.find(this.options.qtySelector).val(parseFloat(it.qty));
                            this._addBlock(it);
                        }.bind(this));

                        if (data && data.generalErrorMessage && data.generalErrorMessage !== '') {
                            this.options.dataError.text = data.generalErrorMessage;
                        }
                        this._reloadError();
                    }.bind(this)
                ).done(function () {
                    qtyElement.prop('readonly', false);

                    // Check if current element doesn't have any item error
                    if (this.element.find('[data-role=error-message]').length === 0) {
                        this.element.next().find(this.options.skuSelector).focus();
                    } else {
                        // Get focus back to field where there is error
                        this.element.find(this.options.skuSelector).focus();
                    }
                }.bind(this));
            } else {
                this._reloadError();
                qtyElement.prop('readonly', false);
            }
        },

        /**
         * Composition data for ajax and sending them to delete item
         *
         * @private
         */
        _deleteByAjax: function () {
            var skuElement = this.element.find(this.options.qtySelector),
                sku = this.element.find(this.options.skuSelector).val();

            this._clearProductBlock();
            skuElement.prop('disabled', true);

            if (sku !== '') {
                $.post(
                    this.options.urlDelete,
                    {
                        'sku': sku
                    },
                    function (data) {
                        if (data && data.generalErrorMessage && data.generalErrorMessage !== '') {
                            this.options.dataError.text = data.generalErrorMessage;
                        }
                        this._reloadError();
                    }.bind(this)
                ).done(function () {
                    skuElement.prop('disabled', false);
                });
            } else {
                this.options.dataError.text = $.mage.__('There is no items to delete');
                this._reloadError();
                skuElement.prop('disabled', false);
            }
        },

        /**
         * Add new block.
         *
         * @param {Object} data
         * @private
         */
        _addBlock: function (data) {
            var addedBlock,
                productBlock;

            // render the form
            addedBlock = $(this.addBlockTmpl({
                data: data
            }));

            // add product info
            productBlock = this.element.find(this.options.addSelector);
            productBlock.html(addedBlock);
            // initialize all mage content
            addedBlock.trigger('contentUpdated');
        },

        /**
         * Check if exist row.
         *
         * @private
         * @return {Boolean} true if row exist and false if not
         */
        _isEmptyRowExist: function () {
            var tableWiget = $(this.options.tableWigetSelector),
                allSkuInputs = tableWiget.find(this.options.skuSelector),
                result = false;

            $.each(allSkuInputs, function () {
                if ($(this).val() == '') { //eslint-disable-line eqeqeq
                    result = true;

                    return false;
                }
            });

            return result;
        },

        /**
         * Check rows for the presence of text.
         *
         * @private
         * @returns {Boolean} true if all fields are empty and false if not
         */
        _isAllRowsEmpty: function () {
            var tableWiget = $(this.options.tableWigetSelector),
                allSkuInputs = tableWiget.find(this.options.skuSelector),
                res = true;

            allSkuInputs.each(function () {
                if (this.value !== '') {
                    res = false;
                }
            });

            return res;
        },

        /**
         * Clear product block from row.
         *
         * @private
         */
        _clearProductBlock: function () {
            var productBlock = this.element.find(this.options.addSelector);

            productBlock.html('');
        }
    });

    return $.mage.productSkuItem;
});
