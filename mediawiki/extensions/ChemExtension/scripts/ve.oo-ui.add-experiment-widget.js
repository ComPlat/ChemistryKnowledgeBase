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
        this.$element.addClass("experiment-dialog")

        let experiments = mw.config.get('experiments');
        this.allExperiments = [];
        for (let e in experiments) {
            this.allExperiments.push({data: e, label: experiments[e].label, type: experiments[e].type});
        }
        let labelType = new OO.ui.LabelWidget({
            label: "Type *"
        });
        this.chooseTypeDropDown = new OO.ui.DropdownInputWidget({
            options: [
                {label: "Assay", data: 'assay'},
                {label: "Molecular process", data: 'molecular-process'}
            ]

        });
        let labelExperimentType = new OO.ui.LabelWidget({
            label: "Investigation-Type *",

        });
        this.chooseTypeDropDown.on('change', (item) => {
            let menuOptions = this.findMenuOptionsOfType(item);
            this.chooseExperimentDropDown.setOptions(menuOptions);
        });

        this.chooseExperimentDropDown = new OO.ui.DropdownInputWidget({
            label: 'Select a investigation type',
            options: this.findMenuOptionsOfType('assay')

        });
        /*this.chooseExperimentDropDown.on('change', (item) => {
            this.setActionsDisabled(['edit', 'insert'], item == '');
        });*/
        let form = data.template ? data.template.params.form.wt : '';
        let selectedForm = this.findMenuOptionsOfForm(form);
        if (selectedForm.length > 0) {
            this.chooseTypeDropDown.setValue(selectedForm[0].type);
            this.chooseExperimentDropDown.setValue(selectedForm[0].data);
        }

        let items = [];

        items.push(labelType);
        items.push(this.chooseTypeDropDown);
        items.push(labelExperimentType);
        items.push(this.chooseExperimentDropDown);

        if (this.mode == 'link') {
            let description = new OO.ui.LabelWidget({
                label: "Description *",
            });
            let descriptionValue =  data.template.params.description ? data.template.params.description.wt : '';
            this.descriptionValue = new OO.ui.MultilineTextInputWidget({value: descriptionValue});
            this.descriptionValue.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], item == '');
            });
            let restrictToPagesLabel = new OO.ui.LabelWidget({
                label: "Restrict to publication pages (empty means all pages)",
            });
            let restrictValue = data.template ? data.template.params.restrictToPages.wt : '';
            let titles = restrictValue.trim() !== '' ? restrictValue.split(",") : [];
            this.restrictInput = new mw.widgets.TitlesMultiselectWidget({selected: titles});
            this.restrictInput.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });

            let queryLabel = new OO.ui.LabelWidget({
                label: "Query to select experiments (empty means all)",
            });
            let queryValue = data.template ? data.template.target.wt : '';
            queryValue = queryValue.replace("#experimentlink:", "");
            queryValue = decodeURIComponent(queryValue);
            this.query = new OO.ui.MultilineTextInputWidget({value: queryValue});
            this.query.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });

            let sortLabel = new OO.ui.LabelWidget({
                label: "Sort for columns (comma-separated)",
            });
            let sortValue =  data.template.params.sort ? data.template.params.sort.wt : '';
            this.sort = new OO.ui.MultilineTextInputWidget({value: sortValue});
            this.sort.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });

            let orderLabel = new OO.ui.LabelWidget({
                label: "Order for columns (comma-separated)",
            });
            let orderValue =  data.template.params.order ? data.template.params.order.wt : '';
            this.order = new OO.ui.MultilineTextInputWidget({value: orderValue});
            this.order.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });

            items.push(description);
            items.push(this.descriptionValue);
            items.push(queryLabel);
            items.push(this.query);
            items.push(restrictToPagesLabel);
            items.push(this.restrictInput);
            items.push(sortLabel);
            items.push(this.sort);
            items.push(orderLabel);
            items.push(this.order);
        } else {

            let experimentNameLabel = new OO.ui.LabelWidget({
                label: "Investigation-Name *",
            });
            let experimentNameValue = data.template ? data.template.params.name.wt : '';
            this.experimentName = new OO.ui.TextInputWidget({value: experimentNameValue});
            this.experimentName.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], item == '');
            });

            let importFileLabel = new OO.ui.LabelWidget({
                label: "Investigation file to import",
            });
            this.importFile = new OO.ui.SelectFileInputWidget();
            items.push(experimentNameLabel);
            items.push(this.experimentName);
            items.push(importFileLabel);
            items.push(this.importFile);
        }


        let formLayout = new OO.ui.FormLayout({
            items: items
        });
        this.$element.append(formLayout.$element);

    }

    OO.ui.ChooseExperimentsWidget.prototype.setActionsDisabled = function (modes, b) {
        let actions = $.grep(this.parent.getActions().list, function (e) {
            return modes.includes(e.modes);
        });
        $.each(actions, function(i, e) {
            e.setDisabled(b);
        });
    }

    OO.ui.ChooseExperimentsWidget.prototype.findMenuOptionsOfType = function (type) {
        return $.grep(this.allExperiments, function (e) {
            return e.type === type
        });
    }

    OO.ui.ChooseExperimentsWidget.prototype.findMenuOptionsOfForm = function (form) {
        return $.grep(this.allExperiments, function (e) {
            return e.data === form
        });
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperiment = function () {
        return this.chooseExperimentDropDown.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperimentName = function () {
        return this.experimentName.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getQuery = function () {
        return this.query.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getRestrictToPages = function () {
        return this.restrictInput.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSortColumns = function () {
        return this.sort.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getOrderColumns = function () {
        return this.order.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getDescription = function () {
        return this.descriptionValue.getValue();
    }

    OO.ui.ChooseExperimentsWidget.prototype.getImportFile = function () {
        return this.importFile.currentFiles;
    }

}(OO));