/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Whitelist grid import button: opens a modal with the upload form.
 *
 * The upload URL, form key, directive list and size limit come from the server-side component configuration.
 */
define([
    'jquery',
    'uiComponent',
    'mage/template',
    'text!Hryvinskyi_Csp/template/grid/import-form.html',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
], function ($, Component, mageTemplate, formTemplate, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_Csp/grid/import',
            url: '',
            formKey: '',
            directives: [],
            maxFileMegabytes: 2
        },

        /**
         * Open the upload form in a modal.
         *
         * @returns {void}
         */
        showImportForm: function () {
            var content = $(mageTemplate(formTemplate, {
                data: {
                    url: this.url,
                    formKey: this.formKey,
                    directives: this.directives.join(', '),
                    maxFileMegabytes: this.maxFileMegabytes
                },
                $t: $t
            }));

            content.modal({
                title: $t('Import Whitelist'),
                modalClass: 'hryvinskyi-csp-import-modal',
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
                        content.find('form').trigger('submit');
                    }
                }]
            }).trigger('openModal');
        }
    });
});
