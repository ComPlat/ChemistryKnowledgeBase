mw.loader.using('ext.visualEditor.core').then(function () {

// -----------------------------------------------------------------------------------------------------------
// insert chemical formula command
// -----------------------------------------------------------------------------------------------------------
    ve.ui.ChemFormCommand = function veUiChemFormCommand(name, action, method, options) {

        // Parent constructor
        ve.ui.ChemFormCommand.super.call(this, name, action, method, options);

    };

    /* Setup */

    OO.inheritClass(ve.ui.ChemFormCommand, ve.ui.Command);

    ve.ui.commandRegistry.register(
        new ve.ui.ChemFormCommand(
            // Command name
            'addChemForm',
            // Type and name of the action to execute
            'content', 'insert', // Calls the ve.ui.ContentAction#insert method
            {
                // Extra arguments for the action
                args: [
                    // Content to insert
                    [
                        {
                            type: 'mwAlienInlineExtension',
                            attributes: {
                                mw: {
                                    name: 'chemform',
                                    attrs: {
                                        'smiles': '',
                                        'inchi': '',
                                        'inchikey': '',
                                        'height': "200px",
                                        'width': "300px",
                                        'float': 'none'
                                    },
                                    body: {
                                        extsrc: ''
                                    }
                                },
                                originalMw: '{"name":"chemform","attrs":{},"body":{"extsrc":""}}'
                            }
                        }

                    ],
                    // Annotate content to match surrounding annotations?
                    true,
                    // Move cursor to after the new content? (otherwise - select it)
                    true
                ],
                supportedSelections: ['linear']
            }
        )
    );

    ve.ui.AddChemForm = function VeUiAddChemForm() {
        ve.ui.AddChemForm.super.apply(this, arguments);
    };
    OO.inheritClass(ve.ui.AddChemForm, ve.ui.Tool);
    ve.ui.AddChemForm.static.name = 'addChemForm';
    ve.ui.AddChemForm.static.group = 'insert';
    ve.ui.AddChemForm.static.title = 'Chemical formula';
    ve.ui.AddChemForm.static.icon = 'chemform';
    ve.ui.AddChemForm.static.commandName = 'addChemForm';
    ve.ui.toolFactory.register(ve.ui.AddChemForm);

// -----------------------------------------------------------------------------------------------------------
// insert Literature command
// -----------------------------------------------------------------------------------------------------------

    ve.ui.AddLiteratureCommand = function veUiAddLiteratureCommand(name, action, method, options) {

        // Parent constructor
        ve.ui.AddLiteratureCommand.super.call(this, name, action, method, options);

    };

    /* Setup */

    OO.inheritClass(ve.ui.AddLiteratureCommand, ve.ui.Command);
    ve.ui.commandRegistry.register(
        new ve.ui.AddLiteratureCommand(
            // Command name
            'addLiterature',
            // Type and name of the action to execute
            'content', 'insert', // Calls the ve.ui.ContentAction#insert method
            {
                // Extra arguments for the action
                args: [
                    // Content to insert
                    [
                        {
                            type: 'mwTransclusionInline',
                            attributes: {
                                mw: {
                                    parts: [
                                        {
                                            template: {
                                                i: 0,
                                                params: {
                                                    doi: {wt: ""}
                                                },
                                                target: {wt: "#literature:", "function": "literature"}
                                            }
                                        }
                                    ]
                                },
                                originalMw: '"{"parts":[{"template":{"target":{"wt":"#literature:","function":"literature"},"params":{"doi":{"wt":""}},"i":0}}]}"'
                            }
                        }

                    ],
                    // Annotate content to match surrounding annotations?
                    true,
                    // Move cursor to after the new content? (otherwise - select it)
                    true
                ],
                supportedSelections: ['linear']
            }
        )
    );

    ve.ui.AddLiterature = function VeUiAddLiterature() {
        ve.ui.AddLiterature.super.apply(this, arguments);
    };
    OO.inheritClass(ve.ui.AddLiterature, ve.ui.Tool);
    ve.ui.AddLiterature.static.name = 'addLiterature';
    ve.ui.AddLiterature.static.group = 'insert';
    ve.ui.AddLiterature.static.title = 'Literature reference';
    ve.ui.AddLiterature.static.icon = 'literature';
    ve.ui.AddLiterature.static.commandName = 'addLiterature';
    ve.ui.toolFactory.register(ve.ui.AddLiterature);

// -----------------------------------------------------------------------------------------------------------
// add molecule link command
// -----------------------------------------------------------------------------------------------------------
    ve.ui.AddMoleculeLinkCommand = function veUiAddMoleculeLinkCommand(name, action, method, options) {

        // Parent constructor
        ve.ui.AddMoleculeLinkCommand.super.call(this, name, action, method, options);

    };

    /* Setup */

    OO.inheritClass(ve.ui.AddMoleculeLinkCommand, ve.ui.Command);
    ve.ui.commandRegistry.register(
        new ve.ui.AddMoleculeLinkCommand(
            // Command name
            'addMoleculeLink',
            // Type and name of the action to execute
            'content', 'insert', // Calls the ve.ui.ContentAction#insert method
            {
                // Extra arguments for the action
                args: [
                    // Content to insert
                    [
                        {
                            type: 'mwTransclusionInline',
                            attributes: {
                                mw: {
                                    parts: [
                                        {
                                            template: {
                                                i: 0,
                                                params: {
                                                    chemformid: {wt: ""}
                                                },
                                                target: {wt: "#moleculelink:", "function": "moleculelink"}
                                            }
                                        }
                                    ]
                                },
                                originalMw: '"{"parts":[{"template":{"target":{"wt":"#moleculelink:","function":"moleculelink"},"params":{"chemformid":{"wt":""}},"i":0}}]}"'
                            }
                        }

                    ],
                    // Annotate content to match surrounding annotations?
                    true,
                    // Move cursor to after the new content? (otherwise - select it)
                    true
                ],
                supportedSelections: ['linear']
            }
        )
    );

    ve.ui.AddMoleculeLink = function VeUiAddMoleculeLink() {
        ve.ui.AddMoleculeLink.super.apply(this, arguments);
    };
    OO.inheritClass(ve.ui.AddMoleculeLink, ve.ui.Tool);
    ve.ui.AddMoleculeLink.static.name = 'addMoleculeLink';
    ve.ui.AddMoleculeLink.static.group = 'insert';
    ve.ui.AddMoleculeLink.static.title = 'Molecule Link';
    ve.ui.AddMoleculeLink.static.icon = 'link';
    ve.ui.AddMoleculeLink.static.commandName = 'addMoleculeLink';
    ve.ui.toolFactory.register(ve.ui.AddMoleculeLink);

// -----------------------------------------------------------------------------------------------------------
// add form input command
// -----------------------------------------------------------------------------------------------------------

    ve.ui.ChooseExperimentDialogCommand = function veUiChooseExperimentDialogCommand(name, action, method, options) {

        // Parent constructor
        ve.ui.ChooseExperimentDialogCommand.super.call(this, name, action, method, options);
        ve.ui.ChooseExperimentDialogCommand.prototype.execute = function (surface, args, source) {

            ve.init.target.getSurface().execute('window', 'open', 'choose-experiments', {
                surface: surface,
            });

        }
    };
    OO.inheritClass(ve.ui.ChooseExperimentDialogCommand, ve.ui.Command);
    ve.ui.commandRegistry.register(new ve.ui.ChooseExperimentDialogCommand('ChooseExperimentDialog', '', '', {}));

    /* Setup */


    ve.ui.ChooseExperimentDialogTool = function VeUiChooseExperimentDialogTool() {
        ve.ui.ChooseExperimentDialogTool.super.apply(this, arguments);
    };
    OO.inheritClass(ve.ui.ChooseExperimentDialogTool, ve.ui.Tool);
    ve.ui.ChooseExperimentDialogTool.static.name = 'ChooseExperimentDialogTool';
    ve.ui.ChooseExperimentDialogTool.static.group = 'insert';
    ve.ui.ChooseExperimentDialogTool.static.title = 'Experiment';
    ve.ui.ChooseExperimentDialogTool.static.icon = 'experiment';
    ve.ui.ChooseExperimentDialogTool.static.commandName = 'ChooseExperimentDialog';
    ve.ui.toolFactory.register(ve.ui.ChooseExperimentDialogTool);
});
