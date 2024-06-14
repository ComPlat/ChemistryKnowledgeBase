(function (OO) {
    'use strict';

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.AjaxEndpoints = function AjaxEndpoints(config) {

        // Properties
        // Initialization

    };

    window.ChemExtension.AjaxEndpoints.prototype.invalidateInvestigationCache = function (data) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/invalidate-inv-cache";

        return $.ajax({
            method: "POST",
            datatype: 'json',
            contentType: "application/json",
            url: url,
            data: data

        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.uploadFile = function (fileName, content) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/uploadfile?fileName=" + encodeURIComponent(fileName);

        return $.ajax({
            method: "POST",
            url: url,
            contentType: "application/octet-stream",
            data: content,
            processData: false
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.uploadImage = function (moleculeKey, imgData) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/upload?moleculeKey=" + encodeURIComponent(moleculeKey);

        return $.ajax({
            method: "POST",
            url: url,
            contentType: "application/x-www-form-urlencoded",
            data: {
                'imgData': imgData,
            }
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.uploadImageAndReplaceOld = function (moleculeKeyOld, moleculeKeyNew, imgData) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/upload?" + $.param({ moleculeKey: moleculeKeyNew, moleculeKeyToReplace: moleculeKeyOld });

        return $.ajax({
            method: "POST",
            url: url,
            contentType: "application/x-www-form-urlencoded",
            data: {
                'imgData': imgData,
            }
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.replaceImage = function (formulaV3000, smiles, inchi, inchikey, moleculeKey, oldMoleculeKey, chemFormId, imgData) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/replace?" + $.param({ moleculeKey: moleculeKey, oldMoleculeKey: oldMoleculeKey, chemFormId : chemFormId });

        return $.ajax({
            method: "POST",
            url: url,
            contentType: "application/x-www-form-urlencoded",
            data: {
                'imgData': imgData,
                'molOrRxn': btoa(formulaV3000),
                'smiles': smiles,
                'inchi': inchi,
                'inchikey': inchikey
            }
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getInchiKey = function (formulaV3000) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/inchi";
        return $.ajax({
            method: "POST",
            url: url,
            data: {
                mol: btoa(formulaV3000)
            }
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getChemFormId = function (moleculekey) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform-id?moleculeKey=" + encodeURIComponent(moleculekey);
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getMoleculeKey = function (chemformid) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/molecule-key?chemformid=" + chemformid;
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.searchForMolecule = function (searchText, restrictTo) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/search-molecule";
        let data = {
            searchText: searchText
        };
        if (restrictTo) {
            data.restrictTo = restrictTo;
        }
        return $.ajax({
            method: "GET",
            url: url,
            data: data
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.searchForTags = function (searchText, restrictTo) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/search-tags";
        let data = {
            searchText: searchText
        };
        if (restrictTo) {
            data.restrictTo = restrictTo;
        }
        return $.ajax({
            method: "GET",
            url: url,
            data: data
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.isJobPending = function (pageId) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/job/pending?pageId=" + pageId;
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.renderImage = function (molfile) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/render";
        let data = { molfile: molfile};
        return $.ajax({
            method: "POST",
            dataType: "json",
            contentType: "application/json",
            url: url,
            data: JSON.stringify(data)
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getPublications = function (category, searchTerm) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/publications";
        return $.ajax({
            method: "GET",
            url: url,
            data: {
                category: category,
                searchTerm: searchTerm
            },
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getInvestigations = function (pageTitle, searchTerm) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/investigations";
        return $.ajax({
            method: "GET",
            url: url,
            data: {
                title: pageTitle,
                searchTerm: searchTerm
            },
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getAvailableRGroups = function () {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/rgroups-available";
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.searchForTitle = function (searchTerm, namespace, withNsPrefix) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/titlesearch";
        let data = {
            searchTerm: searchTerm
        };
        if (namespace) {
            data.namespace = namespace;
        }
        if (withNsPrefix) {
            data.withNsPrefix = withNsPrefix;
        }
        return $.ajax({
            method: "GET",
            url: url,
            data: data,
        });
    }

    OO.initClass(window.ChemExtension.AjaxEndpoints);


}(OO));