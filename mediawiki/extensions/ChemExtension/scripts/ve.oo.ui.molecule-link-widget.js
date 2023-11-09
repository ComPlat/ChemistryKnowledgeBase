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
        let label = new OO.ui.LabelWidget({label: "Molecule-ID (Trivial name, CAS, Abbreviation or Synonyms)"});
        let labelShowAsImage = new OO.ui.LabelWidget({label: "Show molecule image"});
        this.showAsImage = new OO.ui.CheckboxInputWidget();
        let params = data.template.params;
        this.textWidget.setValue(!params.link ? params.chemformid.wt: params.link.wt);
        let imageShown = params.image && params.image.wt === 'true';
        this.showAsImage.setSelected(imageShown);
        let formLayout = new OO.ui.FormLayout({
            items: [label,this.textWidget]
        });
        let formLayout2 = new OO.ui.FormLayout({
            items: [labelShowAsImage, this.showAsImage]
        });
        let labelWidth = new OO.ui.LabelWidget({label: "Width in px"});
        let labelHeight = new OO.ui.LabelWidget({label: "Height in px"});
        this.width = new OO.ui.TextInputWidget();
        this.width.setValue(params.width && params.width.wt ? params.width.wt : 300);
        this.height = new OO.ui.TextInputWidget();
        this.height.setValue(params.height && params.height.wt ? params.height.wt : 200);
        let formLayout3 = new OO.ui.FormLayout({
            items: [labelWidth, this.width]
        });
        let formLayout4 = new OO.ui.FormLayout({
            items: [labelHeight, this.height]
        });
        this.textWidget.on('change', (item) => {
            this.setActionsDisabled(['edit', 'insert'], item === '');
        });
        this.width.setDisabled(!imageShown);
        this.height.setDisabled(!imageShown);
        this.showAsImage.on('change', (item) => {
            this.width.setDisabled(!this.width.isDisabled());
            this.height.setDisabled(!this.height.isDisabled());
        });
        this.$element.append(formLayout.$element);
        this.$element.append(formLayout2.$element);
        this.$element.append(formLayout3.$element);
        this.$element.append(formLayout4.$element);
    }

    OO.ui.ChooseMoleculeWidget.prototype.getMoleculeKey = function() {
        return this.textWidget.getValue();
    }

    OO.ui.ChooseMoleculeWidget.prototype.isShownAsImage = function() {
        return this.showAsImage.isSelected();
    }

    OO.ui.ChooseMoleculeWidget.prototype.getWidth = function() {
        return this.width.getValue();
    }

    OO.ui.ChooseMoleculeWidget.prototype.getHeight = function() {
        return this.height.getValue();
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