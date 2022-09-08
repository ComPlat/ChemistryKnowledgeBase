mw.loader.using('ext.visualEditor.core').then(function () {

    window.ChemExtension = window.ChemExtension || {};

    window.ChemExtension.ShowGroupsDialog = function (config, div) {
        this.div = div;
    }

    window.ChemExtension.ShowGroupsDialog.prototype.initialize = function (data) {

        this.content = new OO.ui.PanelLayout({padded: true, expanded: true});

        this.rgroupsWidget = new OO.ui.RGroupsDisplayWidget({}, this);
        this.rgroupsWidget.setData(data);
        this.content.$element.append(this.rgroupsWidget.$element);

        this.div.append(this.content.$element);

    };

});