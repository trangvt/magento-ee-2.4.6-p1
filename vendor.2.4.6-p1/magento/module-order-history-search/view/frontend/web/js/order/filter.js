/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    $.widget('mage.myOrdersFilter', {
        /**
         * @type {jQuery}
         */
        $form: null,

        /**
         * jQuery elements evaluated during runtime and keyed by respective selector options below
         *
         * @type {Object}
         */
        els: {},

        /**
         * Widget options
         *
         * @type {Object}
         */
        options: {
            filterShowBtn: '#filter-show-btn',
            extraFilters: '#extra-order-search-filters',
            filterCloseBtn: '#filter-close-btn',
            filterSummaryFieldset: '.filter-summary'
        },

        /**
         * Initialize widget handler.
         *
         * @private
         */
        _create: function () {
            this.$form = this.element;

            this.interpolateSelectorOptionsIntoEls();
            this.bindOpenCloseListenerToFilterButton();
            this.createFilterSummary();
        },

        /**
         * Query and collect selector strings as jQuery elements
         */
        interpolateSelectorOptionsIntoEls: function () {
            Object.keys(this.options).forEach(function (optionKey) {
                this.els[optionKey] = $(this.options[optionKey], this.$form);
            }.bind(this));
        },

        /**
         * Bind Open/Close listener to Filter button
         */
        bindOpenCloseListenerToFilterButton: function () {
            this.els.filterShowBtn.click(function () {
                this.els.extraFilters.show();
                this.els.filterCloseBtn.show();
                this.els.filterShowBtn.hide();
                this.els.filterSummaryFieldset.hide();
            }.bind(this));

            this.els.filterCloseBtn.click(function () {
                this.els.extraFilters.hide();
                this.els.filterCloseBtn.hide();
                this.els.filterShowBtn.show();
                this.els.filterSummaryFieldset.show();
            }.bind(this));
        },

        /**
         * Create Filter summary based on non-empty values in form
         */
        createFilterSummary: function () {
            var filters = [],
                $filterList,
                $filterListItems = $(),
                $clearAllFilterListItem,
                currentLabel;

            // collate non-empty filters in the DOM into summarized plain objects
            this.$form.children('.fieldset').children('.field').each(function () {
                var $field = $(this),
                    label = $field.attr('data-filter-label') || $field.children('label').text(),
                    inputs = $field.find(':input').toArray(),
                    filledInputs = inputs.filter(function (input) {
                        return input.value !== '';
                    });

                filters = filters.concat(filledInputs.map(function (filledInput) {
                    var $filledInput = $(filledInput),
                        $subLabel = $filledInput.siblings('.sub-label'),
                        value;

                    // If select, use selected option's text
                    if (filledInput.nodeName === 'SELECT') {
                        value = $filledInput.find(':selected').text();
                    } else { // use value of input field verbatim
                        value = filledInput.value;
                    }

                    return {
                        name: filledInput.getAttribute('name'),
                        label: label,
                        subLabel: $subLabel.text().replace(/:\s*$/, ''),
                        value: value
                    };
                }));
            });

            if (!filters.length) {
                return;
            }

            $filterList = $('<ul />');

            // Create list item for each applied filter field container
            filters.forEach(function (filter) {
                var isNewLabel = currentLabel !== filter.label,
                    $xButton,
                    $filterListItem = isNewLabel ? $('<li />') : $filterListItems.last(),
                    $labelSpan,
                    $subLabelSpan,
                    $valueSpan = $('<span />').attr('data-name', filter.name);

                currentLabel = filter.label;

                if (isNewLabel) {
                    $xButton = $('<a href="#" class="action-remove" />').attr('data-name', filter.name);
                    $filterListItem.prepend($xButton);

                    $labelSpan = $('<span class="label" />').text(currentLabel + ':');
                    $filterListItem.append($labelSpan);

                    $filterListItems = $filterListItems.add($filterListItem);
                }

                if (filter.subLabel.length) {
                    $subLabelSpan = $('<span class="sub-label" />');
                    $subLabelSpan.text(filter.subLabel + ':');
                    $filterListItem.append($subLabelSpan);
                }

                $valueSpan.text(filter.value);
                $filterListItem.append($valueSpan);
            });

            // append Clear All button to end of list items
            $clearAllFilterListItem = $('<li />');
            $clearAllFilterListItem.append(
                $('<a href="#" class="action-remove action-clear-all" />').text($t('Clear All'))
            );

            $filterListItems = $filterListItems.add($clearAllFilterListItem);

            // append filter list items to filter list
            $filterList.append($filterListItems);

            // append filter list to summary fieldset placeholder
            this.els.filterSummaryFieldset.append($filterList);

            this.bindClearInputListenerToActionRemoveButtons();
        },

        /**
         * Bind click to action-remove (X) buttons; removes value on associated input and submits form
         */
        bindClearInputListenerToActionRemoveButtons: function () {
            this.$form.on('click', '.action-remove', function (e) {
                var $button = $(e.currentTarget);

                e.preventDefault();

                if ($button.hasClass('action-clear-all')) {
                    this.$form.find(':input').each(function () {
                        this.value = '';
                    });
                } else {
                    // find every name related with this X (X could belong to more than 1 associated input)
                    $button.siblings('[data-name]').each(function (idx, inputRef) {
                        this.$form.find('[name="' + $(inputRef).attr('data-name') + '"]').val('');
                    }.bind(this));
                }

                this.$form.trigger('submit');
            }.bind(this));
        }
    });

    return $.mage.myOrdersFilter;
});
