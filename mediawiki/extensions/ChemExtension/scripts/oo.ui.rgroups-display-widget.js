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

        this.content = new OO.ui.PanelLayout({padded: true, expanded: false});
        this.content.$element.append('<p>Press Escape key to ' +
            'close.</p>');

        this.getRGroups(moleculeKey, pageid, this.addTable.bind(this));

    }

    OO.ui.RGroupsDisplayWidget.prototype.getBodyHeight = function () {
        return this.content.$element.outerHeight(true);
    };

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

    OO.ui.RGroupsDisplayWidget.prototype.addContent = function () {
        this.$element.append(this.content.$element);
        this.dialog.setDimensions({
            'width': '800px',
            'minWidth': '800px',
            'height': '400px',
            'minHeight': '400px'
        });
    };

    OO.ui.RGroupsDisplayWidget.prototype.addTable = function (response) {

        if (response.length === 0) {
            this.content.$element.append('<p>Can not find any molecules</p>');
            this.addContent();
            return;
        }
        let restIds = Object.keys(response[0].rGroups);
        this.$element.empty();
        let table = $('<table>');
        table.attr('id', 'molecule-rest');
        let headerRow = this.header(restIds);
        table.append(headerRow);

        let that = this;
        $(response).each(function (i, e) {
            let row = $('<tr>');
            let linkToMoleculePage = $('<a>').attr('href', e.molecule_page_url).attr('target', '_blank').text(e.molecule_page_name);
            that.addToolTip(linkToMoleculePage, e.moleculeKey);
            let column = $('<td>');
            column.append(linkToMoleculePage);
            row.append(column);
            for (let i = 0; i < restIds.length; i++) {
                let column = $('<td>');
                let textWidget = new OO.ui.LabelWidget({
                    label: e.rGroups[restIds[i]]
                });

                column.append(textWidget.$element);
                row.append(column);
            }
            table.append(row);
        });

        this.content.$element.append(table);
        this.addContent();
    };

    OO.ui.RGroupsDisplayWidget.prototype.header = function (restIds) {
        let row = $('<tr>');
        let firstColumn = $('<th>');
        row.append(firstColumn);
        for (let i = 0; i < restIds.length; i++) {
            let column = $('<th>');
            let labelWidget = new OO.ui.LabelWidget({
                label: restIds[i].toUpperCase()
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