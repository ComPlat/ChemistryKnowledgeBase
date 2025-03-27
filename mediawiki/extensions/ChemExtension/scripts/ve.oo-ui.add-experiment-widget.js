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
        this.editMode = data.editMode;
        let experiments = mw.config.get('experiments');
        this.allExperiments = [];
        for (let e in experiments) {
            this.allExperiments.push({data: e, label: experiments[e].label, type: experiments[e].type});
        }


        this.chooseTypeDropDown = new OO.ui.DropdownInputWidget({
            options: [
                {label: "Assay", data: 'assay'},
                {label: "Molecular process", data: 'molecular-process'}
            ]

        });
        this.chooseTypeDropDownField = new OO.ui.FieldLayout(
            this.chooseTypeDropDown,
            {
                label: new OO.ui.HtmlSnippet( 'Type *' ),
                align: 'inline'
            }
        );

        this.chooseTypeDropDown.on('change', (item) => {
            let menuOptions = this.findMenuOptionsOfType(item);
            this.chooseExperimentDropDown.setOptions(menuOptions);
        });

        this.chooseExperimentDropDown = new OO.ui.DropdownInputWidget({
            label: 'Select a investigation type',
            options: this.findMenuOptionsOfType('assay')

        });

        this.chooseExperimentDropDownField = new OO.ui.FieldLayout(
            this.chooseExperimentDropDown,
            {
                label: new OO.ui.HtmlSnippet( 'Investigation-Type *' ),
                align: 'inline'
            }
        );

        let form = data.template ? data.template.params.form.wt : '';
        let selectedForm = this.findMenuOptionsOfForm(form);
        if (selectedForm.length > 0) {
            this.chooseTypeDropDown.setValue(selectedForm[0].type);
            this.chooseExperimentDropDown.setValue(selectedForm[0].data);
        }

        let items = [];

        items.push(this.chooseTypeDropDownField);
        items.push(this.chooseExperimentDropDownField);

        if (this.mode == 'link') {

            let descriptionValue =  data.template.params.description ? data.template.params.description.wt : '';
            this.descriptionValue = new OO.ui.MultilineTextInputWidget({value: descriptionValue, required: true});
            this.descriptionValue.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], item == '');
            });
            this.descriptionValueField = new OO.ui.FieldLayout(
                this.descriptionValue,
                {
                    label: new OO.ui.HtmlSnippet( 'Description' ),
                    align: 'inline'
                }
            );

            let restrictValue = data.template ? data.template.params.restrictToPages.wt : '';
            let titles = restrictValue.trim() !== '' ? restrictValue.split(",") : [];
            this.restrictInput = new mw.widgets.TitlesMultiselectWidget({selected: titles});
            this.restrictInput.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });
            this.restrictInputField = new OO.ui.FieldLayout(
                this.restrictInput,
                {
                    label: new OO.ui.HtmlSnippet( 'Restrict to publication pages (empty means all pages)' ),
                    align: 'inline'
                }
            );

            let queryValue = data.template ? data.template.target.wt : '';
            queryValue = queryValue.replace("#experimentlink:", "");
            queryValue = decodeURIComponent(queryValue);
            this.query = new OO.ui.MultilineTextInputWidget({value: queryValue});
            this.query.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });
            this.queryField = new OO.ui.FieldLayout(
                this.query,
                {
                    label: new OO.ui.HtmlSnippet( 'Query to select experiments (empty means all)' ),
                    help: 'Query syntax like used by SMW',
                    helpInline: true,
                    align: 'inline'
                }
            );

            let sortValue =  data.template.params.sort ? data.template.params.sort.wt : '';
            this.sort = new OO.ui.MultilineTextInputWidget({value: sortValue});
            this.sort.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });
            this.sortField = new OO.ui.FieldLayout(
                this.sort,
                {
                    label: new OO.ui.HtmlSnippet( 'Sort for columns (comma-separated)' ),
                    align: 'inline'
                }
            );

            let orderValue =  data.template.params.order ? data.template.params.order.wt : '';
            this.order = new OO.ui.MultilineTextInputWidget({value: orderValue});
            this.order.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], false);
            });
            this.orderField = new OO.ui.FieldLayout(
                this.order,
                {
                    label: new OO.ui.HtmlSnippet( 'Order for columns (comma-separated)' ),
                    align: 'inline'
                }
            );

            items.push(this.descriptionValueField);
            items.push(this.restrictInputField);
            items.push(this.queryField);
            items.push(this.sortField);
            items.push(this.orderField);

        } else {
            let validNamePattern = /^[\w\s_\-+,.()$&:;]+$/;
            let experimentNameValue = data.template ? data.template.params.name.wt : '';
            this.experimentName = new OO.ui.TextInputWidget({value: experimentNameValue, required: true, validate: validNamePattern});
            this.experimentNameField = new OO.ui.FieldLayout(
                this.experimentName,
                {
                    label: new OO.ui.HtmlSnippet( 'Investigation-Name' ),
                    help: 'Name must be unique within the publication',
                    helpInline: true,
                    align: 'inline'
                }
            );

            this.experimentName.on('change', (item) => {
                this.setActionsDisabled(['edit', 'insert'], item == '' || !item.match(validNamePattern));
            });

            let descriptionValue = '';
            if (data.template) {
                descriptionValue = data.template.params.description ? data.template.params.description.wt : '';
            }
            this.descriptionValue = new OO.ui.MultilineTextInputWidget({value: descriptionValue});

            this.descriptionValueField = new OO.ui.FieldLayout(
                this.descriptionValue,
                {
                    label: new OO.ui.HtmlSnippet( 'Description' ),
                    align: 'inline'
                }
            );

            let scriptPath = mw.config.get('wgScriptPath');
            this.importFile = new OO.ui.SelectFileInputWidget();
            this.importFileField = new OO.ui.FieldLayout(
                this.importFile,
                {
                    label: new OO.ui.HtmlSnippet( 'Investigation file to import (optional) <a target="_blank" href="'+scriptPath+'/Help:Investigation_Import_From_File">[Help]</a>' ),
                    help: 'Excel-file (xlsx) to import the investigation data from',
                    helpInline: true,
                    align: 'inline'
                }
            );

            if (data.editMode) {
                this.chooseTypeDropDown.setDisabled(true);
                this.chooseExperimentDropDown.setDisabled(true);
                this.experimentName.setReadOnly(true);
                this.importFile.setDisabled(false);
                this.importFile.on('change', (item) => {
                    this.setActionsDisabled(['edit', 'insert'], item == '');
                });
                this.descriptionValue.on('change', (item) => {
                    this.setActionsDisabled(['edit', 'insert'], item == '');
                });
            }

            items.push(this.descriptionValueField);
            items.push(this.experimentNameField);
            items.push(this.importFileField);

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

    OO.ui.ChooseExperimentsWidget.prototype.isEditMode = function () {
        return this.editMode;
    }

}(OO));