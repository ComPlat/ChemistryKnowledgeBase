/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-MoleculeRGroupsDialog-title')) {
        mw.messages.set({
            've-MoleculeRGroupsDialog-title': 'R-Groups',

        });
    }

    ve.ui.MoleculeRGroupsDialog = function (manager, config) {
        // Parent constructor
        ve.ui.MoleculeRGroupsDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.MoleculeRGroupsDialog, ve.ui.FragmentDialog);


    ve.ui.MoleculeRGroupsDialog.prototype.getActionProcess = function (action) {
        if (action === 'insert' || action === 'done') {
            return new OO.ui.Process(function () {

                let node = this.selectedNode
                let rGroups = this.moleculeRGroupsWidget.getRGroupsAsAttributes();

                for(let r in rGroups) {
                    node.element.attributes.mw.attrs[r] = rGroups[r];
                }

                ve.init.target.fromEditedState = true;
                ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();
                ve.ui.MWMediaDialog.super.prototype.close.call(this);

            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }
    ve.ui.MoleculeRGroupsDialog.prototype.setup = function (data) {

        this.moleculeRGroupsWidget.setData(data.attrs, data.numberOfMoleculeRGroups, data.rGroupIds);
        this.selectedNode = data.node;
        return ve.ui.MoleculeRGroupsDialog.super.prototype.setup.call(this, data);
    };

    ve.ui.MoleculeRGroupsDialog.prototype.getBodyHeight = function () {
        return 600;
    };

    /* Static Properties */
    ve.ui.MoleculeRGroupsDialog.static.name = 'edit-molecule-rgroups';
    ve.ui.MoleculeRGroupsDialog.static.title = mw.msg('ve-MoleculeRGroupsDialog-title');
    ve.ui.MoleculeRGroupsDialog.static.size = 'medium';

    ve.ui.MoleculeRGroupsDialog.static.actions = [
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

    ve.ui.MoleculeRGroupsDialog.prototype.initialize = function () {
        ve.ui.MoleculeRGroupsDialog.super.prototype.initialize.call(this);
        this.setSize("larger");
        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});

        this.moleculeRGroupsWidget = new OO.ui.MoleculeRGroupsWidget();
        this.panel.$element.append(this.moleculeRGroupsWidget.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.MoleculeRGroupsDialog);


});