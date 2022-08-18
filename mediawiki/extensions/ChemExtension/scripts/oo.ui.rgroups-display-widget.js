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

        let inchiKey = data.inchiKey;
        let publicationpageid = data.publicationpageid;

        this.content = new OO.ui.PanelLayout({padded: true, expanded: false});
        this.content.$element.append('<p>Press Escape key to ' +
            'close.</p>');

        this.getRGroups(inchiKey, publicationpageid, this.addTable.bind(this));

    }

    OO.ui.RGroupsDisplayWidget.prototype.getBodyHeight = function () {
        return this.content.$element.outerHeight(true);
    };

    OO.ui.RGroupsDisplayWidget.prototype.getRGroups = function (key, publicationpageid, callback) {
        let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/ChemExtension";
        let url = baseUrl + "/v1/chemform/rgroups?key=" + key + "&publicationpageid=" + publicationpageid;

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
        let restIds = Object.keys(response[0].rests);
        this.$element.empty();
        let table = $('<table>');
        table.attr('id', 'molecule-rest');
        let headerRow = this.header(restIds);
        table.append(headerRow);

        $(response).each(function (i, e) {
            let row = $('<tr>');
            let textWidget = $('<a>').attr('href', e.molecule_page_url).attr('target', '_blank').text(e.molecule_page_name);
            let column = $('<td>');
            column.append(textWidget);
            row.append(column);
            for (let i = 0; i < restIds.length; i++) {
                let column = $('<td>');
                let textWidget = new OO.ui.LabelWidget({
                    label: e.rests[restIds[i]]
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

}(OO));