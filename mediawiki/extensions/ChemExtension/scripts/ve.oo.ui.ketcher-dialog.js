/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-KetcherDialog-title')) {
        mw.messages.set({
            've-KetcherDialog-title': 'Ketcher',

        });
    }

    /* end of translations */


    ve.ui.KetcherDialog = function (manager, config) {
        // Parent constructor
        ve.ui.KetcherDialog.super.call(this, manager, config);
        this.tools = new OO.VisualEditorTools();
        this.ajax = new window.ChemExtension.AjaxEndpoints();
    };
    /* Inheritance */

    OO.inheritClass(ve.ui.KetcherDialog, ve.ui.FragmentDialog);


    ve.ui.KetcherDialog.prototype.getActionProcess = function (action) {
        if (action === 'insert' || action === 'done') {
            return new OO.ui.Process(function () {

                let node = this.selectedNode;
                try {
                    let ketcher = this.tools.getKetcher();
                    if (ketcher == null) {
                        console.error("Ketcher not found.");
                        return;
                    }
                    if (ketcher.containsReaction()) {
                        ketcher.getRxn().then(this.updateReaction.bind(this, node));
                    } else {
                        ketcher.getMolfile('v3000').then(this.updateMolecule.bind(this, node));
                    }
                } catch (e) {
                    mw.notify('Problem occured: ' + e, {type: 'error'});
                }
                ve.ui.MWMediaDialog.super.prototype.close.call(this);

            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }

    ve.ui.KetcherDialog.prototype.updateReaction = function (node, formulaV3000) {
        this.updatePage(node, formulaV3000);
    }

    ve.ui.KetcherDialog.prototype.updateMolecule = function (node, formulaV3000) {

        this.ajax.getInchiKey(formulaV3000).then((response) => {
            this.updatePageWithExternalRendering(node, formulaV3000, response.InChI, response.InChIKey);
            this.openRGroupsDialogIfNoRGroupsDefined(node, formulaV3000);
        }).catch((response) => {
            mw.notify('Problem occured on getting inchikey: ' + response.responseText, {type: 'error'});
        });

    }

    ve.ui.KetcherDialog.prototype.openRGroupsDialogIfNoRGroupsDefined = function (node, formulaV3000) {

        let rGroupIds = this.tools.getRGroupIds(formulaV3000);
        let attrs = node.element.attributes.mw.attrs;
        let open = false;
        for (let i = 0; i < rGroupIds.length; i++) {
            if (!attrs[rGroupIds[i]] || attrs[rGroupIds[i]] == '') {
                open = true;
            }
        }
        if (!open) return;
        ve.init.target.getSurface().execute('window', 'open', 'edit-molecule-rgroups', {
            attrs: node.element.attributes.mw.attrs,
            numberOfMoleculeRGroups: this.tools.getNumberOfMoleculeRGroups(formulaV3000),
            rGroupIds: this.tools.getRGroupIds(formulaV3000),
            node: node
        });
    }

    ve.ui.KetcherDialog.prototype.updatePage = function (node, formula, inchi, inchikey) {

        let ketcher = this.tools.getKetcher();
        let createMoleculeKeyAndUploadImage = this.createMoleculeKeyAndUploadImage.bind(this);
        ketcher.generateImage(formula, {outputFormat: 'svg'}).then((svgBlob) => {
            svgBlob.text().then((imgData) => {
                ketcher.getSmiles().then((smiles) => {
                    createMoleculeKeyAndUploadImage(node, {
                        formula: formula,
                        inchi: inchi,
                        inchikey: inchikey,
                        smiles: smiles,
                        containsReaction: ketcher.containsReaction()
                    }, imgData)
                });
            });
        });

    }


    ve.ui.KetcherDialog.prototype.updatePageWithExternalRendering = function (node, formula, inchi, inchikey) {

        let ketcher = this.tools.getKetcher();
        let createMoleculeKeyAndUploadImage = this.createMoleculeKeyAndUploadImage.bind(this);
        this.ajax.renderImage(formula).then((response) => {
            let imgData = response.svg;
            ketcher.getSmiles().then((smiles) => {
                createMoleculeKeyAndUploadImage(node, {
                    formula: formula,
                    inchi: inchi,
                    inchikey: inchikey,
                    smiles: smiles,
                    containsReaction: ketcher.containsReaction()
                }, imgData)
            });
        }).catch((response) => {
            console.log("Error on rendering image: " + response.responseText);
            mw.notify('Problem occured on rendering image: ' + response.responseText, {type: 'error'});
        });

    }

    ve.ui.KetcherDialog.prototype.createMoleculeKeyAndUploadImage = function (node, formulaData, imgData) {
        let moleculeKey;
        if (this.tools.getNumberOfMoleculeRGroups(formulaData.formula) > 0 || formulaData.containsReaction) {
            moleculeKey = this.tools.createMoleculeKey(formulaData.formula, formulaData.smiles);
        } else {
            moleculeKey = formulaData.inchikey;
        }
        let uploadImagePromise;
        if (this.moleculeKeyOld === '') {
            uploadImagePromise = this.ajax.uploadImage(moleculeKey, btoa(unescape(encodeURIComponent(imgData))));
        } else {
            uploadImagePromise = this.ajax.uploadImageAndReplaceOld(this.moleculeKeyOld, moleculeKey, btoa(unescape(encodeURIComponent(imgData))));
        }
        uploadImagePromise.then(() => {
            this.updateModelAfterUpload(node, {
                formula: formulaData.formula,
                smiles: formulaData.smiles,
                inchi: formulaData.inchi,
                inchikey: formulaData.inchikey
            });
        }).catch((response) => {
            mw.notify('Problem occured on uploading image: ' + response.responseText, {type: 'error'});
        });

    }

    ve.ui.KetcherDialog.prototype.updateModelAfterUpload = function (node, formulaData) {

        //TODO: replace this with a custom transaction
        let rGroupIds = this.tools.getRGroupIds(formulaData.formula);
        this.tools.removeAllNonExistingRGroups(node.element.attributes.mw.attrs, rGroupIds);
        node.element.attributes.mw.body.extsrc = formulaData.formula;

        node.element.attributes.mw.attrs.smiles = formulaData.smiles;
        node.element.attributes.mw.attrs.inchi = formulaData.inchi ? formulaData.inchi : '';
        node.element.attributes.mw.attrs.inchikey = formulaData.inchikey ? formulaData.inchikey : '';

        this.tools.refreshVENode((node) => {
            if (node.type === 'mwAlienInlineExtension' || node.type === 'mwAlienBlockExtension') {
                let formula = node.model.element.attributes.mw.body.extsrc;
                return (formula == formulaData.formula);
            }
            return false;
        });

        ve.init.target.fromEditedState = true;
        ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();

    }

    ve.ui.KetcherDialog.prototype.setup = function (data) {

        this.iframe.setData(data);
        this.selectedNode = data.node;
        this.moleculeKeyOld = data.inchikey;
        return ve.ui.KetcherDialog.super.prototype.setup.call(this, data);
    };

    ve.ui.KetcherDialog.prototype.getBodyHeight = function () {
        return 600;
    };

    /* Static Properties */
    ve.ui.KetcherDialog.static.name = 'edit-with-ketcher';
    ve.ui.KetcherDialog.static.title = mw.msg('ve-KetcherDialog-title');
    ve.ui.KetcherDialog.static.size = 'medium';

    ve.ui.KetcherDialog.static.actions = [
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

    ve.ui.KetcherDialog.prototype.initialize = function () {
        ve.ui.KetcherDialog.super.prototype.initialize.call(this);
        this.setSize("larger");
        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});

        this.iframe = new OO.ui.KetcherWidget();
        this.panel.$element.append(this.iframe.$element);


        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.KetcherDialog);


});