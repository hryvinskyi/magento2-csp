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
                '<div class="admin__field-note" style="margin-top: 15px;">' +
                '<strong>CSV Headers &amp; Values:</strong><br/><br/>' +
                '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">' +
                '<tr style="background: #f5f5f5;"><th style="padding: 5px; border: 1px solid #ddd; text-align: left;">Header</th><th style="padding: 5px; border: 1px solid #ddd; text-align: left;">Type</th><th style="padding: 5px; border: 1px solid #ddd; text-align: left;">Values</th></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>identifier</b></td><td style="padding: 5px; border: 1px solid #ddd;">string</td><td style="padding: 5px; border: 1px solid #ddd;">Unique rule name/description</td></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>policy</b></td><td style="padding: 5px; border: 1px solid #ddd;">string</td><td style="padding: 5px; border: 1px solid #ddd;">default-src, script-src, style-src, img-src, font-src, connect-src, media-src, object-src, frame-src, child-src, manifest-src, base-uri, form-action, frame-ancestors</td></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>value_type</b></td><td style="padding: 5px; border: 1px solid #ddd;">string</td><td style="padding: 5px; border: 1px solid #ddd;">host, hash</td></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>value</b></td><td style="padding: 5px; border: 1px solid #ddd;">string</td><td style="padding: 5px; border: 1px solid #ddd;">URL/domain (for host) or hash value (for hash type)</td></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>value_algorithm</b></td><td style="padding: 5px; border: 1px solid #ddd;">string</td><td style="padding: 5px; border: 1px solid #ddd;">sha256, sha384, sha512 (only required for hash type)</td></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>store_ids</b></td><td style="padding: 5px; border: 1px solid #ddd;">string</td><td style="padding: 5px; border: 1px solid #ddd;">Comma-separated store IDs (0 = All Store Views)</td></tr>' +
                '<tr><td style="padding: 5px; border: 1px solid #ddd;"><b>status</b></td><td style="padding: 5px; border: 1px solid #ddd;">int</td><td style="padding: 5px; border: 1px solid #ddd;">0 = Disabled, 1 = Enabled</td></tr>' +
                '</table>' +
                '</div>' +
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