(function ($) {
    'use strict';

    let ketcherOperation = function () {
        // Parent constructor
        this.tools = new OO.VisualEditorTools();
        this.ajax = new window.ChemExtension.AjaxEndpoints();
        let modifyButtonJQ = $('#modify-molecule');
        if (modifyButtonJQ.length === 0) return;
        this.modifyButton = OO.ui.infuse(modifyButtonJQ);
        this.modifyButton.on('click', (e) => {
            this.modifyButton.setDisabled(true);
            let chemformId = $('#mp-ketcher-editor').attr('chemformid');
            this.saveMolecule(chemformId);
        });
    };
    OO.initClass(ketcherOperation);

    ketcherOperation.prototype.saveMolecule = function (chemformId) {
        try {
            let ketcher = this.tools.getKetcher();
            if (ketcher == null) {
                console.error("Ketcher not found.");
                return;
            }
            ketcher.getMolfile('v3000').then(this.updateMolecule.bind(this, chemformId));
        } catch (e) {
            mw.notify('Problem occured: ' + e, {type: 'error'});
        }
    }

    ketcherOperation.prototype.updateMolecule = function (chemFormId, formulaV3000) {

        this.ajax.getInchiKey(formulaV3000).then((response) => {
            let ketcher = this.tools.getKetcher();
            ketcher.generateImage(formulaV3000, {outputFormat: 'svg'}).then((svgBlob) => {
                svgBlob.text().then((imgData) => {

                    this.ajax.replaceImage(formulaV3000, response.InChIKey, chemFormId, imgData)
                        .then(() => {
                            this.modifyButton.setDisabled(false);
                            mw.notify('Molecule updated');
                        })
                        .catch((res) => {
                            this.modifyButton.setDisabled(false);
                            mw.notify('Problem occured: ' + res.statusText, {type: 'error'});
                        });

                });
            });

        }).catch((response) => {
            mw.notify('Problem updating molecule: ' + response.responseText, {type: 'error'});
        });

    }

    $(function () {
        new ketcherOperation();
    });


}(jQuery));