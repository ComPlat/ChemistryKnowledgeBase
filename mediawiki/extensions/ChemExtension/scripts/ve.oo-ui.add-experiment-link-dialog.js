/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-ChooseExperimentLinkDialog-title')) {
        mw.messages.set({
            've-ChooseExperimentLinkDialog-title': 'Choose investigation type',

        });
    }

    ve.ui.ChooseExperimentLinkDialog = function (manager, config) {
        // Parent constructor
        ve.ui.ChooseExperimentLinkDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.ChooseExperimentLinkDialog, ve.ui.FragmentDialog);


    ve.ui.ChooseExperimentLinkDialog.prototype.getActionProcess = function (action) {
        if (action === 'insert' || action === 'done') {
            return new OO.ui.Process(() => {
                let node = ve.init.target.getSurface().getModel().getSelectedNode();
                let experimentType = this.chooseExperimentsWidget.getSelectedExperiment();
                let query = this.chooseExperimentsWidget.getQuery();
                let restrictToPages = this.chooseExperimentsWidget.getRestrictToPages();
                let sortColumns = this.chooseExperimentsWidget.getSortColumns();
                let orderColumns = this.chooseExperimentsWidget.getOrderColumns();
                let description = this.chooseExperimentsWidget.getDescription();

                let params = node.element.attributes.mw.parts[0].template.params;
                let target = node.element.attributes.mw.parts[0].template.target;

                target.wt = '#experimentlink:' + encodeURIComponent(query);
                params.form = params.form || {};
                params.restrictToPages = params.restrictToPages || {};
                params.sort = params.sort || {};
                params.order = params.order || {};
                params.description = params.description || {};

                params.form.wt = experimentType;
                params.restrictToPages.wt = restrictToPages.join(',');
                params.sort.wt = sortColumns;
                params.order.wt = orderColumns;
                params.description.wt = description;

                let tools = new OO.VisualEditorTools();
                tools.refreshVENode((node) => {
                    if (node.type === 'mwTransclusionBlock' || node.type === 'mwTransclusionInline') {
                        let params = node.model.element.attributes.mw.parts[0].template.params;
                        return (params.form && params.form.wt == experimentType);
                    }
                    return false;
                });

                ve.init.target.fromEditedState = true;
                ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();

                ve.ui.MWMediaDialog.super.prototype.close.call(this);
            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }

    ve.ui.ChooseExperimentLinkDialog.prototype.attachActions = function() {
        ve.ui.ChooseExperimentLinkDialog.super.prototype.attachActions.call(this);
        this.setActionsDisabled(['edit','insert'], true);
    }

    ve.ui.ChooseExperimentLinkDialog.prototype.setActionsDisabled = function (modes, b) {
        let actions = $.grep(this.getActions().list, function (e) {
            return modes.includes(e.modes);
        });
        $.each(actions, function(i, e) {
            e.setDisabled(b);
        });
    }

    ve.ui.ChooseExperimentLinkDialog.prototype.setup = function (data) {
        this.chooseExperimentsWidget.setData(data);
        let setup = ve.ui.ChooseExperimentLinkDialog.super.prototype.setup.call(this, data);
        return setup;
    };

    ve.ui.ChooseExperimentLinkDialog.prototype.getBodyHeight = function () {
        return 550;
    };

    /* Static Properties */
    ve.ui.ChooseExperimentLinkDialog.static.name = 'choose-experiment-link';
    ve.ui.ChooseExperimentLinkDialog.static.title = mw.msg('ve-ChooseExperimentLinkDialog-title');
    ve.ui.ChooseExperimentLinkDialog.static.size = 'medium';

    ve.ui.ChooseExperimentLinkDialog.static.actions = [
        {
            action: 'done',
            label: OO.ui.deferMsg( 'visualeditor-dialog-action-apply' ),
            flags: [ 'progressive', 'primary' ],
            modes: 'edit'
        },
        {
            action: 'insert',
            label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
            flags: [ 'progressive', 'primary' ],
            modes: 'insert'
        },
        {
            label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
            flags: [ 'safe', 'close' ],
            modes: [ 'readonly', 'insert', 'edit', 'insert-select' ]
        }
    ];

    ve.ui.ChooseExperimentLinkDialog.prototype.initialize = function () {
        ve.ui.ChooseExperimentLinkDialog.super.prototype.initialize.call(this);

        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});
        this.chooseExperimentsWidget = new OO.ui.ChooseExperimentsWidget(this, {mode: 'link'});
        this.panel.$element.append(this.chooseExperimentsWidget.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.ChooseExperimentLinkDialog);


});