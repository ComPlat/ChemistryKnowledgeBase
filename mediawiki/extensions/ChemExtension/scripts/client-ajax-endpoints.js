(function (OO) {
    'use strict';

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.AjaxEndpoints = function AjaxEndpoints(config) {

        // Properties
        // Initialization

    };

    window.ChemExtension.AjaxEndpoints.prototype.editExperiment = function (request) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/edit-experiment";

        return $.ajax({
            method: "POST",
            datatype: 'json',
            contentType: "application/json",
            url: url,
            data: JSON.stringify(request)

        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.renamePage = function (oldPageTitle, newPageTitle) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/renamePage";
        let data = {
            oldPageTitle: oldPageTitle,
            newPageTitle: newPageTitle
        }
        return $.ajax({
            method: "POST",
            datatype: 'json',
            contentType: "application/json",
            url: url,
            data: JSON.stringify(data)

        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.invalidateInvestigationLinkCache = function (data) {
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

    window.ChemExtension.AjaxEndpoints.prototype.invalidateInvestigationListCache = function (data) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/invalidate-inv-list-cache";

        return $.ajax({
            method: "POST",
            datatype: 'json',
            contentType: "application/json",
            url: url,
            data: data

        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.getSMILESFromPubChem  = function (inchikey) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/pub-chem?inchikey=" + encodeURIComponent(inchikey);
        return $.ajax({
            method: "GET",
            url: url
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.exportExperiment = function (data) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/export-investigation";

        return $.ajax({
            method: "POST",
            contentType: "application/json",
            url: url,
            data: data,
            xhrFields: {
                responseType: 'blob' // to avoid binary data being mangled on charset conversion
            },
            success: (blob, status, xhr) => {
                this.downloadFile(blob, status, xhr);
            }
        });
    }

    window.ChemExtension.AjaxEndpoints.prototype.importExperiment = function (data) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/import-investigation";

        return $.ajax({
            method: "POST",
            datatype: 'json',
            contentType: "application/json",
            url: url,
            data: JSON.stringify(data)
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

    window.ChemExtension.AjaxEndpoints.prototype.downloadFile = function(blob, status, xhr) {
        // check for a filename
        var filename = "";
        var disposition = xhr.getResponseHeader('Content-Disposition');
        if (disposition && disposition.indexOf('attachment') !== -1) {
            var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            var matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
        }

        if (typeof window.navigator.msSaveBlob !== 'undefined') {
            // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
            window.navigator.msSaveBlob(blob, filename);
        } else {
            var URL = window.URL || window.webkitURL;
            var downloadUrl = URL.createObjectURL(blob);

            if (filename) {
                // use HTML5 a[download] attribute to specify filename
                var a = document.createElement("a");
                // safari doesn't support this yet
                if (typeof a.download === 'undefined') {
                    window.location.href = downloadUrl;
                } else {
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                }
            } else {
                window.location.href = downloadUrl;
            }

            setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
        }
    };

    OO.initClass(window.ChemExtension.AjaxEndpoints);


}(OO));