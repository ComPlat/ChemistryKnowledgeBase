(function (OO) {
    'use strict';

    OO.ui.ChooseExperimentsWidget = function OoUiChooseExperimentsWidget(config) {
        // Configuration initialization
        config = config || {};

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
        this.chooseTypeDropDown = new OO.ui.DropdownInputWidget({
            options: [
                { label: "Assay", data: 'assay'},
                { label: "Molecule process", data: 'molecule-process'}
            ]

        });
        this.chooseTypeDropDown.on('change', (item) => {
            let menuOptions = this.findMenuOptionsOfType(item);
            this.chooseExperimentDropDown.getMenu().clearItems().addItems(menuOptions);
        });

        this.chooseExperimentDropDown = new OO.ui.DropdownWidget({
            label: 'Select a experiment type',
            menu: {
                items:  this.findMenuOptionsOfType('assay')
            }
        });
        this.$element.append(this.chooseTypeDropDown.$element);
        this.$element.append(this.chooseExperimentDropDown.$element);
    }

    OO.ui.ChooseExperimentsWidget.prototype.findMenuOptionsOfType = function(type) {
        let itemsOfType = $.grep(this.allExperiments, function(e) { return e.type === type });
        return $.map(itemsOfType, function (e) { return new OO.ui.MenuOptionWidget(e); });
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperiment = function () {
        return this.chooseExperimentDropDown.getMenu().findSelectedItem().getData();
    }


}(OO));