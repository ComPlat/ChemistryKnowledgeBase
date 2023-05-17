(function (OO) {
    'use strict';

    OO.ui.ChooseMoleculeWidget = function OoUiChooseMoleculeWidget(parent, config) {
        // Configuration initialization
        config = config || {};

        this.parent = parent;
        // Parent constructor
        OO.ui.ChooseMoleculeWidget.super.call(this, config);

        // Properties

        // Initialization

    };

    /* Setup */

    OO.inheritClass(OO.ui.ChooseMoleculeWidget, OO.ui.Widget);

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.ChooseMoleculeWidget.static.tagName = 'div';

    OO.ui.ChooseMoleculeWidget.prototype.setData = function (data) {
        this.$element.empty();
        this.textWidget = new OO.ui.InchiKeyLookupTextInputWidget();
        let label = new OO.ui.LabelWidget({label: "Molecule-ID"});
        let params = data.template.params;
        this.textWidget.setValue(!params.link ? params.chemformid.wt: params.link.wt);
        let formLayout = new OO.ui.FormLayout({
            items: [label,this.textWidget]
        })
        this.textWidget.on('change', (item) => {
            this.setActionsDisabled(['edit', 'insert'], item === '');
        });
        this.$element.append(formLayout.$element);
    }

    OO.ui.ChooseMoleculeWidget.prototype.getChemFormId = function() {
        return this.textWidget.getValue();
    }

    OO.ui.ChooseMoleculeWidget.prototype.getErrorLabel = function() {
        return this.errorLabel;
    }

    OO.ui.ChooseMoleculeWidget.prototype.setActionsDisabled = function (modes, b) {
        let actions = $.grep(this.parent.getActions().list, function (e) {
            return modes.includes(e.modes);
        });
        $.each(actions, function(i, e) {
            e.setDisabled(b);
        });
    }


}(OO));