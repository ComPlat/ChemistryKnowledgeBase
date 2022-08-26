(function (OO) {
    'use strict';

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.AjaxEndpoints = function AjaxEndpoints(config) {

        // Properties
        // Initialization

    };

    window.ChemExtension.AjaxEndpoints.prototype.uploadImage = function (moleculeKey, imgData, callback) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/upload?moleculeKey=" + encodeURIComponent(moleculeKey);

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

    window.ChemExtension.AjaxEndpoints.prototype.getInchiKey = function (node, formulaV3000) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/inchi?mol=" + btoa(formulaV3000);
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    OO.initClass(window.ChemExtension.AjaxEndpoints);


}(OO));