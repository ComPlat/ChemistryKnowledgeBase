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
        if (action === 'apply') {
            return new OO.ui.Process(() => {
                let node = ve.init.target.getSurface().getModel().getSelectedNode();
                let experimentType = this.chooseExperimentsWidget.getSelectedExperiment();
                let experimentName = this.chooseExperimentsWidget.getSelectedExperimentName();
                let experimentQuery = this.chooseExperimentsWidget.getSelectedIndices();
                let experimentPage = this.chooseExperimentsWidget.getSelectedPage();
                let params = node.element.attributes.mw.parts[0].template.params;
                params.page = params.page || {};
                params.form = params.form || {};
                params.name = params.name || {};
                params.query = params.query || {};

                params.page.wt = experimentPage;
                params.form.wt = experimentType;
                params.name.wt = experimentName;
                params.query.wt = experimentQuery;

                let tools = new OO.VisualEditorTools();
                tools.refreshVENode((node) => {
                    if (node.type === 'mwTransclusionBlock' || node.type === 'mwTransclusionInline') {
                        let params = node.model.element.attributes.mw.parts[0].template.params;
                        return (params.name && params.name.wt == experimentName);
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
        this.getActions().list[0].setDisabled(true);
    }

    ve.ui.ChooseExperimentLinkDialog.prototype.setup = function (data) {
        this.chooseExperimentsWidget.setData(data);
        let setup = ve.ui.ChooseExperimentLinkDialog.super.prototype.setup.call(this, data);
        return setup;
    };

    ve.ui.ChooseExperimentLinkDialog.prototype.getBodyHeight = function () {
        return 400;
    };

    /* Static Properties */
    ve.ui.ChooseExperimentLinkDialog.static.name = 'choose-experiment-link';
    ve.ui.ChooseExperimentLinkDialog.static.title = mw.msg('ve-ChooseExperimentLinkDialog-title');
    ve.ui.ChooseExperimentLinkDialog.static.size = 'medium';

    ve.ui.ChooseExperimentLinkDialog.static.actions = [
        {
            'action': 'apply',
            'label': mw.msg('visualeditor-dialog-action-apply'),
            'flags': ['safe'],
            'modes': ['edit', 'insert', 'select']
        },
        {
            'label': OO.ui.deferMsg('visualeditor-dialog-action-cancel'),
            'flags': 'safe',
            'modes': ['edit', 'insert', 'select']
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