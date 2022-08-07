/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-MoleculeRestsDialog-title')) {
        mw.messages.set({
            've-MoleculeRestsDialog-title': 'Molecule rests',

        });
    }

    ve.ui.MoleculeRestsDialog = function (manager, config) {
        // Parent constructor
        ve.ui.MoleculeRestsDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.MoleculeRestsDialog, ve.ui.FragmentDialog);


    ve.ui.MoleculeRestsDialog.prototype.getActionProcess = function (action) {
        if (action === 'apply') {
            return new OO.ui.Process(function () {

                let node = this.selectedNode
                let rests = this.moleculeRests.getRestsAsAttributes();

                for(let r in rests) {
                    node.element.attributes.mw.attrs[r] = rests[r];
                }

                console.log(rests);
                ve.init.target.fromEditedState = true;
                ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();
                ve.ui.MWMediaDialog.super.prototype.close.call(this);

            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }
    ve.ui.MoleculeRestsDialog.prototype.setup = function (data) {

        this.moleculeRests.setData(data.attrs, data.numberOfMoleculeRests, data.restIds);
        this.selectedNode = data.node;
        return ve.ui.MoleculeRestsDialog.super.prototype.setup.call(this, data);
    };

    ve.ui.MoleculeRestsDialog.prototype.getBodyHeight = function () {
        return 600;
    };

    /* Static Properties */
    ve.ui.MoleculeRestsDialog.static.name = 'edit-molecule-rests';
    ve.ui.MoleculeRestsDialog.static.title = mw.msg('ve-MoleculeRestsDialog-title');
    ve.ui.MoleculeRestsDialog.static.size = 'medium';

    ve.ui.MoleculeRestsDialog.static.actions = [
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

    ve.ui.MoleculeRestsDialog.prototype.initialize = function () {
        ve.ui.MoleculeRestsDialog.super.prototype.initialize.call(this);
        this.setSize("larger");
        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});

        this.moleculeRests = new OO.ui.MoleculeRestsWidget();
        this.panel.$element.append(this.moleculeRests.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.MoleculeRestsDialog);


});