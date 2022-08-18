mw.loader.using('ext.visualEditor.core').then(function () {

    window.ChemExtension = window.ChemExtension || {};

    window.ChemExtension.ShowGroupsDialog = function (config) {
        window.ChemExtension.ShowGroupsDialog.super.call(this, config);
    }

    OO.inheritClass(window.ChemExtension.ShowGroupsDialog, OO.ui.Dialog);
    window.ChemExtension.ShowGroupsDialog.static.name = 'showRGroups';

    window.ChemExtension.ShowGroupsDialog.prototype.setup = function (data) {
        this.rgroupsWidget.setData(data);

        return window.ChemExtension.ShowGroupsDialog.super.prototype.setup.call(this, data);
    }

    window.ChemExtension.ShowGroupsDialog.prototype.initialize = function () {
        window.ChemExtension.ShowGroupsDialog.super.prototype.initialize.call(this);
        this.content = new OO.ui.PanelLayout({padded: true, expanded: true});


        this.rgroupsWidget = new OO.ui.RGroupsDisplayWidget({}, this);
        this.content.$element.append(this.rgroupsWidget.$element);

        this.$body.append(this.content.$element);

    };
    window.ChemExtension.ShowGroupsDialog.prototype.getBodyHeight = function () {
        return this.content.$element.outerHeight(true);
    };

});