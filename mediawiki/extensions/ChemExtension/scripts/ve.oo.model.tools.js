(function (OO) {
    'use strict';

    OO.VisualEditorTools = function OoVisualEditorTools(config) {

        // Properties
        // Initialization

    };

    OO.VisualEditorTools.prototype.createMoleculeKey = function (formula, smiles) {
        let rGroupIds = this.getRGroupIds(formula);
        let key = smiles + rGroupIds.join('');
        if (key.length > 255) {
            key = window.ChemExtension.md5(key);
        }
        return key;
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

    OO.VisualEditorTools.prototype.renderFormula = function (downloadURL, tooltip) {
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

    OO.VisualEditorTools.prototype.refreshVENode = function (predicate) {
        let nodesToUpdate = [];
        let documentNode = ve.init.target.getSurface().getView().getDocument().getDocumentNode();
        let iterate = function (node) {
            if (!node.children) return;
            for (let i = 0; i < node.children.length; i++) {
                let child = node.children[i];
                if (predicate(child)) {
                    nodesToUpdate.push(child);
                }
                iterate(child);
            }
        }
        iterate(documentNode);
        for (let i = 0; i < nodesToUpdate.length; i++) {
            nodesToUpdate[i].forceUpdate();
        }
    }

    OO.VisualEditorTools.prototype.createCookie = function(name, value, days) {
        var expires;
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toGMTString();
        }
        else {
            expires = "";
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    OO.VisualEditorTools.prototype.getCookie = function(c_name) {
        if (document.cookie.length > 0) {
            let c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                let c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) {
                    c_end = document.cookie.length;
                }
                return unescape(document.cookie.substring(c_start, c_end));
            }
        }
        return "";
    }

    OO.initClass(OO.VisualEditorTools);


}(OO));