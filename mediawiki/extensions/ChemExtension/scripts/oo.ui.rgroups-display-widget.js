(function (OO) {
    'use strict';

    OO.ui.RGroupsDisplayWidget = function OoUiRGroupsDisplayWidget(config, dialog) {
        // Configuration initialization
        config = config || {};
        this.dialog = dialog;
        // Parent constructor
        OO.ui.RGroupsDisplayWidget.super.call(this, config);

        // Properties
        this.input = config.input;

        // Initialization


    };

    /* Setup */

    OO.inheritClass(OO.ui.RGroupsDisplayWidget, OO.ui.Widget);

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.RGroupsDisplayWidget.static.tagName = 'div';

    OO.ui.RGroupsDisplayWidget.prototype.setData = function (data) {

        let moleculeKey = data.moleculeKey;
        let pageid = data.pageid;

        this.controls = new OO.ui.PanelLayout({ expanded: false});
        this.image = new OO.ui.PanelLayout({expanded: false});
        this.content = new OO.ui.PanelLayout({padded: true, expanded: false});
        this.content.$element.addClass('rgroups-molecule');
        this.content.$element.append(this.controls.$element);
        this.content.$element.append(this.image.$element);
        var closeButton = new OO.ui.ButtonWidget({
            label: 'close'
        });
        let closeHandler =  function () {
            this.dialog.div.remove();
        };
        closeButton.on('click', closeHandler.bind(this));
        this.controls.$element.append(closeButton.$element);

        this.right = new OO.ui.PanelLayout({padded: true, expanded: false});
        this.right.$element.addClass('rgroups-list');

        this.$element.append(this.content.$element);
        this.$element.append(this.right.$element);
        this.getMoleculeTemplateImage(moleculeKey);
        this.getRGroups(moleculeKey, pageid, this.addTable.bind(this));
        this.isJobPending(pageid);
    }

    OO.ui.RGroupsDisplayWidget.prototype.isJobPending = function(pageid) {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        ajax.isJobPending(pageid).done((response) => {
            if (response.jobPending) {
                this.content.$element.append($('<span>').addClass('hint-blinking').text('Job is pending'));
            }
        });
    }

    OO.ui.RGroupsDisplayWidget.prototype.getMoleculeTemplateImage = function(moleculeKey) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let downloadURL = baseUrl + "/v1/chemform?moleculeKey=" + encodeURIComponent(moleculeKey);
        let that = this;
        fetch(downloadURL).then(r => {

            if (r.status != 200) {
                that.image.$element.append("Could not load molecule image");
                return;
            }
            r.blob().then(function (blob) {
                const img = new Image();
                img.src = URL.createObjectURL(blob);
                img.style.width = "200px";
                img.style.height = "300px";

                that.image.$element.append(img);

            });

        });
    }

    OO.ui.RGroupsDisplayWidget.prototype.getRGroups = function (key, pageid, callback) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/rgroups?moleculekey=" + encodeURIComponent(key) + "&pageid=" + pageid;

        return $.ajax({
            method: "GET",
            url: url,
            success: function (response) {
                callback(response);
            }
        });
    }


    OO.ui.RGroupsDisplayWidget.prototype.addTable = function (response) {

        if (response.length === 0) {
            this.right.$element.append('<p>Can not find any molecules</p>');

            return;
        }
        let rGroupIds = Object.keys(response[0].rGroups);
        this.right.$element.empty();
        let table = $('<table>');
        table.attr('id', 'molecule-rest');
        let headerRow = this.header(rGroupIds);
        table.append(headerRow);

        let that = this;
        $(response).each(function (i, e) {
            let row = $('<tr>');
            let linkToMoleculePage = $('<a>').attr('href', e.molecule_page_url).attr('target', '_blank').text(e.molecule_page_name);
            that.addToolTip(linkToMoleculePage, e.moleculeKey);
            let column = $('<td>');
            column.append(linkToMoleculePage);
            row.append(column);
            for (let i = 0; i < rGroupIds.length; i++) {
                let column = $('<td>');
                let textWidget = new OO.ui.LabelWidget({
                    label: e.rGroups[rGroupIds[i]]
                });

                column.append(textWidget.$element);
                row.append(column);
            }
            table.append(row);
        });

        this.right.$element.append(table);

    };

    OO.ui.RGroupsDisplayWidget.prototype.header = function (rGroupIds) {
        let row = $('<tr>');
        let firstColumn = $('<th>');
        row.append(firstColumn);
        for (let i = 0; i < rGroupIds.length; i++) {
            let column = $('<th>');
            let labelWidget = new OO.ui.LabelWidget({
                label: rGroupIds[i].toUpperCase()
            });
            column.append(labelWidget.$element);
            row.append(column);
        }
        return row;
    }

    OO.ui.RGroupsDisplayWidget.prototype.addToolTip = function(div, moleculeKey) {
        div.qtip({
            content: "<div></div>",
            style: { classes: 'chemformula-tooltip' },
            events: {

                render: function(event, api) {
                    // Grab the tooltip element from the API
                    let tooltip = api.elements.tooltip;
                    let downloadURL = mw.config.get('wgScriptPath') + "/rest.php/ChemExtension/v1/chemform?moleculeKey="+encodeURIComponent(moleculeKey);
                    fetch(downloadURL).then(r => {

                        if (r.status != 200) {
                            image.append('Image does not exist. Please re-save in editor.');
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
            },
            position: {
                at: 'top right'
            }
        });
    }


}(OO));