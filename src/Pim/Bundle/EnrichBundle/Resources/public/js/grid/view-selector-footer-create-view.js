'use strict';

/**
 * Create view button extension for the Datagrid footer View Selector.
 *
 * Contains a "create" button to allow the user to create a view from the current
 * state of the grid (regarding filters and columns).
 *
 * @author    Adrien Petremann <adrien.petremann@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'jquery',
        'underscore',
        'oro/translator',
        'pim/form',
        'backbone',
        'text!pim/template/grid/view-selector/footer/create/view',
        'text!pim/template/grid/view-selector/create-view-label-input',
        'pim/dialog',
        'routing',
        'pim/datagrid/state',
        'pim/saver/datagrid-view',
        'oro/messenger'
    ],
    function (
        $,
        _,
        __,
        BaseForm,
        Backbone,
        template,
        templateInput,
        Dialog,
        Routing,
        DatagridState,
        DatagridViewSaver,
        messenger
    ) {
        return BaseForm.extend({
            template: _.template(template),
            templateInput: _.template(templateInput),
            tagName: 'span',
            className: 'action',

            /**
             * {@inheritdoc}
             */
            configure: function () {
                this.listenTo(this, 'grid:view-selector:create-new', this.handleViewCreation);

                return BaseForm.prototype.configure.apply(this, arguments);
            },

            /**
             * We check if this extension can handle the action by checking the extension code.
             *
             * @param {Object} data '{extensionCode: "the_extension_code"}'
             */
            handleViewCreation: function (data) {
                if (data.extensionCode === this.code) {
                    this.promptCreateView();
                }
            },

            /**
             * {@inheritdoc}
             */
            render: function () {
                this.$el.html(this.template({
                    viewButtonTitle: __('grid.view_selector.create_view')
                }));

                return this;
            },

            /**
             * Prompt the view creation modal.
             */
            promptCreateView: function () {
                this.getRoot().trigger('grid:view-selector:close-selector');

                var modal = new Backbone.BootstrapModal({
                    title: __('grid.view_selector.choose_label'),
                    content: this.templateInput({placeholder: __('grid.view_selector.placeholder')}),
                    okText: __('pim_datagrid.view_selector.create_view_modal.confirm'),
                    cancelText: __('pim_datagrid.view_selector.create_view_modal.cancel')
                });
                modal.open();

                var $submitButton = modal.$el.find('.ok').hide();

                modal.on('ok', this.saveView.bind(this, modal));
                modal.on('cancel', function () {
                    modal.remove();
                }.bind(this));
                modal.$('input[name="new-view-label"]').on('input', function (event) {
                    var label = event.target.value;

                    if (!label.length) {
                        $submitButton.hide();
                    } else {
                        $submitButton.show();
                    }
                });
                modal.$('input[name="new-view-label"]').on('keypress', function (event) {
                    if (13 === (event.keyCode || event.which) && event.target.value.length) {
                        $submitButton.trigger('click');
                    }
                });
            },

            /**
             * Save the current Datagrid view in database and triggers an event to the parent
             * to select it.
             *
             * @param {object} modal
             */
            saveView: function (modal) {
                var gridState = DatagridState.get(this.getRoot().gridAlias, ['filters', 'columns']);
                var newView = {
                    filters: gridState.filters,
                    columns: gridState.columns,
                    label: modal.$('input[name="new-view-label"]').val()
                };

                DatagridViewSaver.save(newView, this.getRoot().gridAlias)
                    .done(function (response) {
                        this.getRoot().trigger('grid:view-selector:view-created', response.id);
                    }.bind(this))
                    .fail(function (response) {
                        _.each(response.responseJSON, function (error) {
                            messenger.notificationFlashMessage('error', error);
                        });
                    });
            }
        });
    }
);
