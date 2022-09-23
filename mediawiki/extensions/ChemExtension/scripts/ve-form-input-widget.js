(function (OO) {
    'use strict';

    OO.ui.ChooseExperimentsWidget = function OoUiChooseExperimentsWidget(config) {
        // Configuration initialization
        config = config || {};

        // Parent constructor
        OO.ui.ChooseExperimentsWidget.super.call(this, config);

        // Properties
        this.input = config.input;

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

    OO.ui.ChooseExperimentsWidget.prototype.setData = function (attrs, numberOfMoleculeRGroups, rGroupIds) {
        this.$element.empty();
        let experiments = mw.config.get('experiments');
        let items = [];
        for(let e in experiments) {
            items.push({ data: e, label: experiments[e].label});
        }
        this.chooseExperimentDropDown = new OO.ui.DropdownWidget({
            label: 'Select one',
            menu: {
                items: $.map(items, function (e) { return new OO.ui.MenuOptionWidget(e); })
            }
        });
        this.$element.append(this.chooseExperimentDropDown.$element);
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperiment = function () {
        return this.chooseExperimentDropDown.getMenu().findSelectedItem().getData();
    }


}(OO));