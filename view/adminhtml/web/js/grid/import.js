/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'jquery',
    'uiComponent',
    'mage/template',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, uiComponent, mageTemplate, modal, $t) {
    'use strict';

    return uiComponent.extend({
        defaults: {
            template: 'Hryvinskyi_Csp/grid/import',
        },

        /**
         * Show import form in modal
         */
        showImportForm: function () {
            var self = this;
            var formHtml = '<form id="import-form" enctype="multipart/form-data" method="post" action="' + this.url + '">' +
                '<div class="admin__field">' +
                '<label class="admin__field-label" for="import_file"><span>' + $t('Select File to Import') + '</span></label>' +
                '<div class="admin__field-control">' +
                '<input name="form_key" type="hidden" value="' + window.FORM_KEY + '" />' +
                '<input id="import_file" name="import_file" type="file" class="admin__control-file" required />' +
                '<div class="admin__field-note"><span>' + $t('Allowed file types: csv, xml') + '</span></div>' +
                '<div class="admin__field-note"><span>Required headers: identifier, policy, value_type, value, store_ids, status</span></div>' +
                '</div>' +
                '</div>' +
                '</form>';

            $('<div/>').html(formHtml).modal({
                title: $t('Import Data'),
                modalClass: 'import-modal',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }, {
                    text: $t('Import'),
                    class: 'action-primary',
                    click: function () {
                        $('#import-form').submit();
                        this.closeModal();
                    }
                }]
            }).trigger('openModal');
        }
    });
});