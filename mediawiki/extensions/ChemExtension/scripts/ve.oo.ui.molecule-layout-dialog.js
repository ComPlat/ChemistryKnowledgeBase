/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-MoleculeLayoutDialog-title')) {
        mw.messages.set({
            've-MoleculeLayoutDialog-title': 'Layout',
        });
    }

    ve.ui.MoleculeLayoutDialog = function (manager, config) {
        // Parent constructor
        ve.ui.MoleculeLayoutDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.MoleculeLayoutDialog, ve.ui.FragmentDialog);


    ve.ui.MoleculeLayoutDialog.prototype.getActionProcess = function (action) {
        if (action === 'insert' || action === 'done') {
            return new OO.ui.Process(function () {

                let params = this.attrs;
                let width = this.width.getValue();
                let height = this.height.getValue();
                let marginLeft = this.marginLeft.getValue();
                let marginRight = this.marginRight.getValue();
                params.width = width;
                params.height = height;
                params.margin = "0px "+marginRight+"px 0px "+marginLeft+"px";

                let tools = new OO.VisualEditorTools();
                tools.refreshVENode((node) => {
                    if (node.type === 'mwAlienInlineExtension') {
                        let params = node.model.element.attributes.mw.attrs;
                        return (params && params.width == width && params.height == height);
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

    ve.ui.MoleculeLayoutDialog.prototype.setup = function (data) {

        this.attrs = data.attrs;
        this.width.setValue(this.attrs.width);
        this.height.setValue(this.attrs.height);
        let margin = this.attrs.margin || '0px 0px 0px 0px';
        margin = margin.replaceAll('px', '');
        let parts = margin.split(' ');
        this.marginLeft.setValue(parts[3]);
        this.marginRight.setValue(parts[1]);
        return ve.ui.MoleculeLayoutDialog.super.prototype.setup.call(this, data);
    };

    ve.ui.MoleculeLayoutDialog.prototype.getBodyHeight = function () {
        return 380;
    };

    /* Static Properties */
    ve.ui.MoleculeLayoutDialog.static.name = 'edit-molecule-layout';
    ve.ui.MoleculeLayoutDialog.static.title = mw.msg('ve-MoleculeLayoutDialog-title');
    ve.ui.MoleculeLayoutDialog.static.size = 'medium';

    ve.ui.MoleculeLayoutDialog.static.actions = [
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

    ve.ui.MoleculeLayoutDialog.prototype.initialize = function () {
        ve.ui.MoleculeLayoutDialog.super.prototype.initialize.call(this);
        this.setSize("larger");
        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});

        // add GUI elements
        let labelWidth = new OO.ui.LabelWidget({label: "Width in px"});
        let labelHeight = new OO.ui.LabelWidget({label: "Height in px"});
        this.width = new OO.ui.TextInputWidget();
        this.width.setValue('');
        this.height = new OO.ui.TextInputWidget();
        this.height.setValue('');
        let formLayout1 = new OO.ui.FormLayout({
            items: [labelWidth, this.width]
        });
        let formLayout2 = new OO.ui.FormLayout({
            items: [labelHeight, this.height]
        });
        let labelMarginLeft = new OO.ui.LabelWidget({label: "Margin left in px"});
        this.marginLeft = new OO.ui.TextInputWidget();
        this.marginLeft.setValue('');

        let formLayout3 = new OO.ui.FormLayout({
            items: [labelMarginLeft, this.marginLeft]
        });
        let labelMarginRight = new OO.ui.LabelWidget({label: "Margin right in px"});
        this.marginRight = new OO.ui.TextInputWidget();
        this.marginRight.setValue('');

        let formLayout4 = new OO.ui.FormLayout({
            items: [labelMarginRight, this.marginRight]
        });


        this.panel.$element.append(formLayout1.$element);
        this.panel.$element.append(formLayout2.$element);
        this.panel.$element.append(formLayout3.$element);
        this.panel.$element.append(formLayout4.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.MoleculeLayoutDialog);


});