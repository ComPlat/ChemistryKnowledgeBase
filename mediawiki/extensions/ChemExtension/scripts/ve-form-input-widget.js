(function (OO) {
    'use strict';

    OO.ui.ChooseExperimentsWidget = function OoUiChooseExperimentsWidget(parent, config) {
        // Configuration initialization
        config = config || {};

        this.parent = parent;
        // Parent constructor
        OO.ui.ChooseExperimentsWidget.super.call(this, config);

        // Properties

        // Initialization

    };

    /* Setup */

    OO.inheritClass(OO.ui.ChooseExperimentsWidget, OO.ui.Widget);

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.ChooseExperimentsWidget.static.tagName = 'div';

    OO.ui.ChooseExperimentsWidget.prototype.setData = function () {
        this.$element.empty();

        let experiments = mw.config.get('experiments');
        this.allExperiments = [];
        for(let e in experiments) {
            this.allExperiments.push({ data: e, label: experiments[e].label, type: experiments[e].type });
        }
        let labelType = new OO.ui.LabelWidget({
            label: "Type"
        });
        this.chooseTypeDropDown = new OO.ui.DropdownInputWidget({
            options: [
                { label: "Assay", data: 'assay'},
                { label: "Molecular process", data: 'molecular-process'}
            ]

        });
        let labelExperimentType = new OO.ui.LabelWidget({
            label: "Investigation-Type",

        });
        this.chooseTypeDropDown.on('change', (item) => {
            let menuOptions = this.findMenuOptionsOfType(item);
            this.chooseExperimentDropDown.setOptions(menuOptions);
        });

        this.chooseExperimentDropDown = new OO.ui.DropdownInputWidget({
            label: 'Select a investigation type',
            options: this.findMenuOptionsOfType('assay')
        });
        this.chooseExperimentDropDown.on('change', (item) => {
            //this.parent.getActions().list[0].setDisabled(false);
        });

        let formLayout = new OO.ui.FormLayout({
            items: [labelType,this.chooseTypeDropDown, labelExperimentType, this.chooseExperimentDropDown]
        });
        this.$element.append(formLayout.$element);

    }

    OO.ui.ChooseExperimentsWidget.prototype.findMenuOptionsOfType = function(type) {
        return $.grep(this.allExperiments, function(e) { return e.type === type });
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperiment = function () {
        return this.chooseExperimentDropDown.getValue();
    }


}(OO));