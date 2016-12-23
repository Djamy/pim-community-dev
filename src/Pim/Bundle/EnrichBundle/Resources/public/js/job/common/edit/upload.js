'use strict';

define([
    'jquery',
    'underscore',
    'oro/translator',
    'pim/form',
    'text!pim/template/export/common/edit/upload',
    'oro/messenger',
    'pim/common/property'

], function ($, _, __, BaseForm, template, messenger, propertyAccessor) {
    return BaseForm.extend({
        template: _.template(template),
        events: {
            'change input[type="file"]': 'addFile',
            'click .clear-field': 'removeFile'
        },

        /**
         * {@inheritdoc}
         */
        render: function () {
            this.$el.html(this.template({
                file: this.getFormData().file
            }));

            return this;
        },

        addFile: function () {
            var input = this.$('input[type="file"]').get(0);
            if (!input || 0 === input.files.length) {
                return;
            }

            this.setData({file: input.files[0]});

            this.getRoot().trigger('pim_enrich:form:job:file_updated');

            this.render();
        },

        removeFile: function () {
            this.setData({file: null});

            this.getRoot().trigger('pim_enrich:form:job:file_updated');

            this.render();
        }
    });
});