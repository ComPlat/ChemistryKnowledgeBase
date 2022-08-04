/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-KetcherDialog-title')) {
        mw.messages.set({
            've-KetcherDialog-title': 'Ketcher',

        });
    }

    /* end of translations */

    function getKetcher() {
        if (window.ketcher) {
            return window.ketcher;
        }
        for (var i = 0; i < window.frames.length; i++) {
            if (window.frames[i].window.ketcher) {
                return window.frames[i].window.ketcher;
            }
        }
        return null;
    }

    function fixMol(formula) {
        //FIXME: formula contains Smiles at the beginning. bug in ketcher?
        return "\n" + formula.substr(formula.indexOf("Ketcher"));
    }

    function uploadImage(id, imgData, callback) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/upload?id=" + id;

        return $.ajax({
            method: "POST",
            url: url,
            contentType: "application/x-www-form-urlencoded",
            data: {
                'imgData': imgData,
            },
            success: function () {
                callback();
            }
        });
    }

    function updatePage(node, formula, inchi, inchikey) {
        let ketcher = getKetcher();
        ketcher.generateImage(formula, {outputFormat: 'svg'}).then(function (svgBlob) {
            svgBlob.text().then(function (imgData) {
                ketcher.getSmiles().then(function (smiles) {
                    let tools = new OO.VisualEditorTools();
                    let id = node.element.attributes.mw.attrs.id;
                    if (id == '') {
                        node.element.attributes.mw.attrs.id = tools.newID();
                    }
                    uploadImage(inchikey ? inchikey : id, btoa(imgData), function () {
                        //TODO: replace this with a custom transaction
                        node.element.attributes.mw.body.extsrc = formula;

                        node.element.attributes.mw.attrs.smiles = smiles;
                        if (inchi) {
                            node.element.attributes.mw.attrs.inchi = inchi;
                        } else {
                            node.element.attributes.mw.attrs.inchi = '';
                        }
                        if (inchikey) {
                            node.element.attributes.mw.attrs.inchikey = inchikey;
                        } else {
                            node.element.attributes.mw.attrs.inchikey = '';
                        }
                        node.element.attributes.mw.attrs.isReaction = ketcher.containsReaction();
                        ve.init.target.getSurface().getModel().getDocument().rebuildTree();
                        ve.init.target.fromEditedState = true;
                        ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();
                    });

                });

            });
        });

    }

    function getInchI(mol) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/inchi?mol=" + btoa(mol);
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    ve.ui.KetcherDialog = function (manager, config) {
        // Parent constructor
        ve.ui.KetcherDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.KetcherDialog, ve.ui.FragmentDialog);


    ve.ui.KetcherDialog.prototype.getActionProcess = function (action) {
        if (action === 'apply') {
            return new OO.ui.Process(function () {

                let node = this.selectedNode;
                try {
                    let ketcher = getKetcher();
                    if (ketcher == null) {
                        console.error("Ketcher not found.");
                        return;
                    }
                    if (ketcher.containsReaction()) {
                        ketcher.getRxn().then(function (formula) {
                            updatePage(node, formula);
                        });
                    } else {
                        ketcher.getMolfile('v3000').then(function (formulaV3000) {
                            getInchI(formulaV3000).done(function (response) {

                                let inchi = response.InChI;
                                let inchikey = response.InChIKey;

                                updatePage(node, formulaV3000, inchi, inchikey);

                            });
                        });
                    }
                } catch (e) {
                    mw.notify('Problem occured: ' + e, {type: 'error'});
                }
                ve.ui.MWMediaDialog.super.prototype.close.call(this);

            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }
    ve.ui.KetcherDialog.prototype.setup = function (data) {

        this.iframe.setData(data.formula, data.id);
        this.selectedNode = data.node;
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