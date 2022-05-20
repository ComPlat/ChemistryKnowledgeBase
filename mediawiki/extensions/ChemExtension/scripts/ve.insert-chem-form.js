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
});
