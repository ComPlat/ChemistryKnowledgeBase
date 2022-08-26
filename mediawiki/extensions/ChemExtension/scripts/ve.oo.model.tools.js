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

    OO.initClass(OO.VisualEditorTools);


}(OO));