/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-ChooseExperimentDialog-title')) {
        mw.messages.set({
            've-ChooseExperimentDialog-title': 'Choose investigation type',

        });
    }

    ve.ui.ChooseExperimentDialog = function (manager, config) {
        // Parent constructor
        ve.ui.ChooseExperimentDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.ChooseExperimentDialog, ve.ui.FragmentDialog);

    ve.ui.ChooseExperimentDialog.prototype.getActionProcess = function (action) {
        if (action === 'insert' || action === 'done') {
            return new OO.ui.Process(() => {
                let selectedExperiment = this.chooseExperimentsWidget.getSelectedExperiment();
                let selectedExperimentName = this.chooseExperimentsWidget.getSelectedExperimentName();

                if (this.chooseExperimentsWidget.isEditMode()) {
                    let description = this.chooseExperimentsWidget.getDescription();
                    let experimentType = this.chooseExperimentsWidget.getSelectedExperiment();
                    let node = ve.init.target.getSurface().getModel().getSelectedNode();
                    let params = node.element.attributes.mw.parts[0].template.params;
                    params.description = params.description || {};
                    params.description.wt = description;
                    let importFile = this.chooseExperimentsWidget.getImportFile();
                    if (importFile && importFile.length > 0) this.uploadFileAndCreateJob(importFile, selectedExperiment, selectedExperimentName);
                    let tools = new OO.VisualEditorTools();
                    tools.refreshVENode((node) => {
                        if (node.type === 'mwTransclusionBlock' || node.type === 'mwTransclusionInline') {
                            let params = node.model.element.attributes.mw.parts[0].template.params;
                            return (params.form && params.form.wt == experimentType);
                        }
                        return false;
                    });

                    ve.init.target.fromEditedState = true;
                    ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();

                    ve.ui.MWMediaDialog.super.prototype.close.call(this);
                    return;
                }

                let importFile = this.chooseExperimentsWidget.getImportFile();
                let description = this.chooseExperimentsWidget.getDescription();
                if (importFile.length > 0) {
                    this.uploadFileAndCreateJob(importFile, selectedExperiment, selectedExperimentName, () => {
                        this.insertExperiment(selectedExperiment, selectedExperimentName, importFile[0].name, description);
                    });
                } else {
                    this.insertExperiment(selectedExperiment, selectedExperimentName, '', description);
                }

            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }

    ve.ui.ChooseExperimentDialog.prototype.uploadFileAndCreateJob = function(importFile, selectedExperiment, selectedExperimentName, onPostUpload) {
        var progressDialog = new ve.ui.IndefiniteProgressDialog({ showText: 'Uploading...' });
        var windowManager = new OO.ui.WindowManager();
        $( document.body ).append( windowManager.$element );
        windowManager.addWindows( [ progressDialog ] );
        windowManager.openWindow( progressDialog);
        let file = importFile[0], read = new FileReader();
        read.readAsArrayBuffer(file);
        read.onloadend = () => {
            let ajax = new window.ChemExtension.AjaxEndpoints();
            ajax.uploadFile(importFile[0].name, read.result).done(() => {
                progressDialog.close();
                if (onPostUpload) onPostUpload();
                ajax.importExperiment({
                    'publicationPage': mw.config.get('wgPageName'),
                    'filename': importFile[0].name,
                    'selectedExperiment': selectedExperiment,
                    'selectedExperimentName': selectedExperimentName
                }).done((e) => {
                    mw.notify('Importjob successfully created.')
                })
            }).fail((e) => {
                progressDialog.close();
                mw.notify('Error occured on file upload')
                console.log(e);
            });
        }
    };

    ve.ui.ChooseExperimentDialog.prototype.insertExperiment = function(selectedExperiment, selectedExperimentName, importFileName, description) {
        let toInsert = [ [
            {
                type: 'mwTransclusionInline',
                attributes: {
                    mw: {
                        parts:  [
                            {
                                template: {
                                    i: 0,
                                    params: {
                                        form: { wt: selectedExperiment },
                                        name: { wt: selectedExperimentName },
                                        importFile: { wt: importFileName },
                                        description: { wt: description }
                                    },
                                    target: { wt: "#experimentlist:", "function": "experimentlist"}
                                }
                            }
                        ]
                    },
                    originalMw: '"{"parts":[{"template":{"target":{"wt":"#experimentlist:","function":"experimentlist"},"params":{"form":{"wt":""}, "name":{"wt":""}, "importFile":{"wt":""}, "description":{"wt":""}},"i":0}}]}"'
                }
            }

        ]];
        this.surface.execute.apply( this.surface, [ 'content', 'insert' ].concat( toInsert ) );
        ve.ui.MWMediaDialog.super.prototype.close.call(this);
    }

    ve.ui.ChooseExperimentDialog.prototype.attachActions = function() {
        ve.ui.ChooseExperimentDialog.super.prototype.attachActions.call(this);
        this.setActionsDisabled(['edit','insert'],true);
    }

    ve.ui.ChooseExperimentDialog.prototype.setActionsDisabled = function (modes, b) {
        let actions = $.grep(this.getActions().list, function (e) {
            return modes.includes(e.modes);
        });
        $.each(actions, function(i, e) {
            e.setDisabled(b);
        });
    }

    ve.ui.ChooseExperimentDialog.prototype.setup = function (data) {
        this.chooseExperimentsWidget.setData(data);
        this.surface = data.surface;
        let setup = ve.ui.ChooseExperimentDialog.super.prototype.setup.call(this, data);
        return setup;
    };

    ve.ui.ChooseExperimentDialog.prototype.getBodyHeight = function () {
        return 530;
    };

    /* Static Properties */
    ve.ui.ChooseExperimentDialog.static.name = 'choose-experiments';
    ve.ui.ChooseExperimentDialog.static.title = mw.msg('ve-ChooseExperimentDialog-title');
    ve.ui.ChooseExperimentDialog.static.size = 'medium';

    ve.ui.ChooseExperimentDialog.static.actions = [
        {
            action: 'done',
            label: OO.ui.deferMsg( 'visualeditor-dialog-action-apply' ),
            flags: [ 'progressive', 'primary' ],
            modes: 'edit'
        },
        {
            action: 'insert',
            label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
            flags: [ 'progressive', 'primary' ],
            modes: 'insert'
        },
        {
            label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
            flags: [ 'safe', 'close' ],
            modes: [ 'readonly', 'insert', 'edit', 'insert-select' ]
        }
    ];

    ve.ui.ChooseExperimentDialog.prototype.initialize = function () {
        ve.ui.ChooseExperimentDialog.super.prototype.initialize.call(this);

        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});
        this.chooseExperimentsWidget = new OO.ui.ChooseExperimentsWidget(this, {mode: 'list'});
        this.panel.$element.append(this.chooseExperimentsWidget.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.ChooseExperimentDialog);


});