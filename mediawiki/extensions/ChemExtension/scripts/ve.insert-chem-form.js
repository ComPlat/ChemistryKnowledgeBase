mw.libs.ve.addPlugin( function ( target ) {
    // Some code to run when edit surface is ready
    //var surface = ve.init.target.getSurface();

    ve.ui.commandRegistry.register(
        new ve.ui.Command(
            // Command name
            'addChemForm',
            // Type and name of the action to execute
            'content', 'insert', // Calls the ve.ui.ContentAction#insert method
            {
                // Extra arguments for the action
                args: [
                    // Content to insert
                    [
                        { type: 'mwAlienInlineExtension',
                            attributes: {
                                mw: {
                                    name: 'chemform',
                                    attrs: {
                                        'id': Math.random().toString(16).slice(2)
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
                supportedSelections: [ 'linear' ]
            }
        )
    );

    ve.ui.AddChemForm = function VeUiAddChemForm() {
        ve.ui.AddChemForm.super.apply( this, arguments );
    };
    OO.inheritClass( ve.ui.AddChemForm, ve.ui.Tool );
    ve.ui.AddChemForm.static.name = 'addChemForm';
    ve.ui.AddChemForm.static.group = 'insert';
    ve.ui.AddChemForm.static.title = 'Add chemical formula';
    ve.ui.AddChemForm.static.commandName = 'addChemForm';
    ve.ui.toolFactory.register( ve.ui.AddChemForm );
} );