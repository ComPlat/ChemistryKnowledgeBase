/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-ChooseMoleculeDialog-title')) {
        mw.messages.set({
            've-ChooseMoleculeDialog-title': 'Choose molecule',

        });
    }

    ve.ui.ChooseMoleculeDialog = function (manager, config) {
        // Parent constructor
        ve.ui.ChooseMoleculeDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.ChooseMoleculeDialog, ve.ui.FragmentDialog);


    ve.ui.ChooseMoleculeDialog.prototype.getActionProcess = function (action) {
        if (action === 'apply') {
            return new OO.ui.Process(() => {
                let node = ve.init.target.getSurface().getModel().getSelectedNode();
                let inchiKey = this.chooseMoleculeWidget.getChemFormId();
                let params = node.element.attributes.mw.parts[0].template.params;
                params.link = params.link || {};
                params.link.wt = inchiKey;

                let tools = new OO.VisualEditorTools();
                tools.refreshVENode((node) => {
                    if (node.type === 'mwTransclusionBlock' || node.type === 'mwTransclusionInline') {
                        let params = node.model.element.attributes.mw.parts[0].template.params;
                        return (params.link && params.link.wt == inchiKey);
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

    ve.ui.ChooseMoleculeDialog.prototype.attachActions = function() {
        ve.ui.ChooseMoleculeDialog.super.prototype.attachActions.call(this);
    }

    ve.ui.ChooseMoleculeDialog.prototype.setup = function (data) {
        this.chooseMoleculeWidget.setData(data);
        this.selectedNode = data.node;
        return ve.ui.ChooseMoleculeDialog.super.prototype.setup.call(this, data);

    };

    ve.ui.ChooseMoleculeDialog.prototype.getBodyHeight = function () {
        return 200;
    };

    /* Static Properties */
    ve.ui.ChooseMoleculeDialog.static.name = 'choose-molecule';
    ve.ui.ChooseMoleculeDialog.static.title = mw.msg('ve-ChooseMoleculeDialog-title');
    ve.ui.ChooseMoleculeDialog.static.size = 'medium';

    ve.ui.ChooseMoleculeDialog.static.actions = [
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

    ve.ui.ChooseMoleculeDialog.prototype.initialize = function () {
        ve.ui.ChooseMoleculeDialog.super.prototype.initialize.call(this);

        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});
        this.chooseMoleculeWidget = new OO.ui.ChooseMoleculeWidget(this);
        this.panel.$element.append(this.chooseMoleculeWidget.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.ChooseMoleculeDialog);


});