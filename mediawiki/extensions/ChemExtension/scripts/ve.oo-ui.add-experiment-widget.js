(function (OO) {
    'use strict';

    OO.ui.ChooseExperimentsWidget = function OoUiChooseExperimentsWidget(parent, config) {
        // Configuration initialization
        config = config || {};

        this.parent = parent;
        this.mode = config.mode || '';
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

    OO.ui.ChooseExperimentsWidget.prototype.setData = function (data) {
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
        let items = [];
        if (this.mode != 'link') {
            items.push(labelType);
            items.push(this.chooseTypeDropDown);
            items.push(labelExperimentType);
            items.push(this.chooseExperimentDropDown);
        }
        let experimentNameLabel = new OO.ui.LabelWidget({
            label: "Investigation-Name",
        });
        let experimentNameValue = data.template ? data.template.params.name.wt : '';
        this.experimentName = new OO.ui.TextInputWidget({value: experimentNameValue});
        this.experimentName.on('change', (item) => {
            this.parent.getActions().list[0].setDisabled(item == '');
        });

        if (this.mode == 'link') {

            let queryLabel = new OO.ui.LabelWidget({
                label: "Query to select experiments (empty means all)",
            });
            let queryValue = data.template ? data.template.params.query.wt : '';
            this.query = new OO.ui.MultilineTextInputWidget({value: queryValue});
            this.query.on('change', (item) => {
                this.parent.getActions().list[0].setDisabled(item == '');
            });

            let pageLabel = new OO.ui.LabelWidget({
                label: "Page containing the investigation",
            });
            let pageValue = data.template ? data.template.params.page.wt : '';
            this.page = new mw.widgets.TitleInputWidget({value: pageValue});
            items.push(pageLabel);
            items.push(this.page);
            items.push(experimentNameLabel);
            items.push(this.experimentName);
            items.push(queryLabel);
            items.push(this.query);
        } else {
            items.push(experimentNameLabel);
            items.push(this.experimentName);
        }


        let formLayout = new OO.ui.FormLayout({
            items: items
        });
        this.$element.append(formLayout.$element);

    }

    OO.ui.ChooseExperimentsWidget.prototype.findMenuOptionsOfType = function(type) {
        return $.grep(this.allExperiments, function(e) { return e.type === type });
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperiment = function () {
        return this.chooseExperimentDropDown.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperimentName = function () {
        return this.experimentName.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedIndices = function () {
        return this.query.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedPage = function () {
        return this.page.getValue();
    }


}(OO));