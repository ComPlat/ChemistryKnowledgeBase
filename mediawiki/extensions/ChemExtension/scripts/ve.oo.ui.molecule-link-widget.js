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
        this.textWidget = new OO.ui.TextInputWidget({
            disabled: true
        });
        let label = new OO.ui.LabelWidget({
            label: "Molecule-ID",

        });
        this.errorLabel = new OO.ui.LabelWidget({
            classes: ['error']
        });

        let ajax = new window.ChemExtension.AjaxEndpoints();
        if (!data.template.params.link) { // to be removed
            this.textWidget.setDisabled(false);
            this.textWidget.setValue(data.template.params.chemformid.wt);
        } else {
            ajax.getChemFormId(data.template.params.link.wt).then((response) => {
                this.textWidget.setValue(response.chemformid);
                this.textWidget.setDisabled(false);
            }).catch(() => {
                this.textWidget.setDisabled(false);
            });
        }
        let formLayout = new OO.ui.FormLayout({
            items: [label,this.textWidget, this.errorLabel]
        })

        this.$element.append(formLayout.$element);
    }

    OO.ui.ChooseMoleculeWidget.prototype.getChemFormId = function() {
        return this.textWidget.getValue();
    }

    OO.ui.ChooseMoleculeWidget.prototype.getErrorLabel = function() {
        return this.errorLabel;
    }


}(OO));