(function ($) {
    'use strict';

    let ketcherOperation = function () {
        // Parent constructor
        this.tools = new OO.VisualEditorTools();
        this.ajax = new window.ChemExtension.AjaxEndpoints();
        let moleculeKeyField = $('#moleculeKey');
        this.moleculeKeyField = OO.ui.infuse(moleculeKeyField);
        let modifyButtonJQ = $('#modify-molecule');
        if (modifyButtonJQ.length === 0) return;
        this.modifyButton = OO.ui.infuse(modifyButtonJQ);
        this.modifyButton.on('click', (e) => {
            this.modifyButton.setDisabled(true);
            let chemformId = $('#mp-ketcher-editor').attr('chemformid');
            let moleculeKey = $('#mp-ketcher-editor').attr('moleculeKey');
            this.saveMolecule(chemformId, moleculeKey);
        });
    };
    OO.initClass(ketcherOperation);

    ketcherOperation.prototype.saveMolecule = function (chemformId, moleculeKey) {
        try {
            let ketcher = this.tools.getKetcher();
            if (ketcher == null) {
                console.error("Ketcher not found.");
                return;
            }
            ketcher.getMolfile('v3000').then(this.updateMolecule.bind(this, chemformId, moleculeKey));
        } catch (e) {
            mw.notify('Problem occured: ' + e, {type: 'error'});
        }
    }

    ketcherOperation.prototype.updateMolecule = function (chemFormId, moleculeKey, formulaV3000) {

        this.ajax.getInchiKey(formulaV3000).then((response) => {
            let ketcher = this.tools.getKetcher();
            this.ajax.renderImage(formulaV3000).then((responseRender) => {
                let imgData = responseRender.svg;
                    ketcher.getSmiles().then((smiles) => {
                        let newMoleculeKey;
                        if (this.tools.getNumberOfMoleculeRGroups(formulaV3000) > 0) {
                            newMoleculeKey = this.tools.createMoleculeKey(formulaV3000, smiles);
                        } else {
                            newMoleculeKey = response.InChIKey;
                        }
                        this.ajax.replaceImage(formulaV3000, smiles, response.InChI, response.InChIKey, newMoleculeKey, moleculeKey, chemFormId, imgData)
                            .then(() => {
                                this.modifyButton.setDisabled(false);
                                this.moleculeKeyField.setValue(newMoleculeKey);
                                $('#mp-ketcher-editor').attr('moleculeKey', newMoleculeKey);
                                OO.ui.alert('Molecule was successfully updated.');
                            })
                            .catch((res) => {
                                this.modifyButton.setDisabled(false);
                                if (res.status === 409) {
                                    OO.ui.alert('Problem occured: ' + res.responseText, {type: 'error'});
                                } else {
                                    OO.ui.alert('Problem occured: ' + res.responseText, {type: 'error'});
                                }
                            });
                    });

                });
            }).catch((response) => {
            OO.ui.alert('Problem updating molecule: ' + response.responseText, {type: 'error'});
        });

    }

    $(function () {
        OO.ui.infuse($('#moleculeKey'));
        new ketcherOperation();
    });


}(jQuery));