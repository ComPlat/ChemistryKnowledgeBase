(function (OO) {
    'use strict';

    OO.VisualEditorTools = function OoVisualEditorTools(config) {

        // Properties
        // Initialization

    };

    OO.VisualEditorTools.prototype.createMoleculeKey = function(formula, smiles) {
        let rGroupIds = this.getRGroupIds(formula);
        return smiles + rGroupIds.join('');
    }

    OO.VisualEditorTools.prototype.getNumberOfMoleculeRGroups = function (formula) {
        let m = formula.matchAll(/RGROUPS=/g);
        let i = 0;
        while (m.next().value) {
            i++;
        }
        return i;
    }

    OO.VisualEditorTools.prototype.getRGroupIds = function (formula) {
        let m = formula.matchAll(/RGROUPS=\((\d+)\s*(\d+)/g);
        let rGroupIds = [];
        let next = m.next().value;
        while (next) {
            if (rGroupIds.indexOf("r" + next[2]) === -1) {
                rGroupIds.push("r" + next[2]);
            }
            next = m.next().value;
        }

        return rGroupIds.sort();
    }

    OO.VisualEditorTools.prototype.removeAllNonExistingRGroups = function (attrs, rGroupIds) {
        for (let a in attrs) {
            if (a.match(/r\d+/) && rGroupIds.indexOf(a) === -1) {
                delete attrs[a];
            }
        }
    }

    OO.VisualEditorTools.prototype.getKetcher = function () {

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

    OO.VisualEditorTools.prototype.renderFormula = function(downloadURL, tooltip) {
        if (downloadURL === '') {
            return;
        }
        fetch(downloadURL).then(r => {

            if (r.status != 200) {
                tooltip.append('Image does not exist. Please re-save in editor.');
                return;
            }
            r.blob().then(function (blob) {
                const img = new Image();
                img.src = URL.createObjectURL(blob);
                img.style.width = "100%";
                img.style.height = "95%";

                tooltip.append(img);

            });

        });

    }

    OO.VisualEditorTools.prototype.refreshTransclusionNode = function(predicate) {
        let nodesToUpdate = [];
        let documentNode = ve.init.target.getSurface().getView().getDocument().getDocumentNode();
        let iterate = function(node) {
            if (!node.children) return;
            for(let i = 0; i < node.children.length; i++) {
                let child = node.children[i];
                if (child.type === 'mwTransclusionBlock' || child.type === 'mwTransclusionInline') {
                    if (predicate(child)) {
                        nodesToUpdate.push(child);
                    }
                }
                iterate(child);
            }
        }
        iterate(documentNode);
        for(let i = 0; i < nodesToUpdate.length; i++) {
            nodesToUpdate[i].forceUpdate();
        }
    }

    OO.initClass(OO.VisualEditorTools);


}(OO));