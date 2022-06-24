mw.loader.using('ext.visualEditor.core').then(function () {
        ve.ui.ChemFormCommand = function veUiChemFormCommand(name, action, method, options) {

            // Parent constructor
            ve.ui.ChemFormCommand.super.call(this, name, action, method, options);

        };

        /* Setup */

        OO.inheritClass(ve.ui.ChemFormCommand, ve.ui.Command);

        ve.ui.ChemFormCommand.prototype.execute = function (surface, args, source) {
            this.args[0][0].attributes.mw.attrs.id = Math.random().toString(16).slice(2);
            ve.ui.ChemFormCommand.super.prototype.execute.call(this, surface, args, source);
        }

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
                                            'id': '',
                                            'smiles': '',
                                            'isReaction': '',
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
                            type: 'mwTransclusionBlock',
                            attributes: {
                                mw: {
                                    parts:  [
                                        {
                                            template: {
                                                i: 0,
                                                params: {
                                                    doi: { wt: "" }
                                                },
                                                target: { wt: "#literature:", "function": "literature"}
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
});
