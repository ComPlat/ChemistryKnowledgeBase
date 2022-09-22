( function ( OO ) {
    'use strict';

    OO.ui.ChooseExperimentsWidget = function OoUiChooseExperimentsWidget( config ) {
        // Configuration initialization
        config = config || {};

        // Parent constructor
        OO.ui.ChooseExperimentsWidget.super.call( this, config );

        // Properties
        this.input = config.input;

        // Initialization


    };

    /* Setup */

    OO.inheritClass( OO.ui.ChooseExperimentsWidget, OO.ui.Widget );

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.ChooseExperimentsWidget.static.tagName = 'div';

    OO.ui.ChooseExperimentsWidget.prototype.setData = function(attrs, numberOfMoleculeRGroups, rGroupIds) {
        this.$element.empty();
        this.chooseExperimentDropDown = new OO.ui.DropdownWidget( {
            label: 'Select one',
            menu: {
                items: [
                    new OO.ui.MenuOptionWidget( {
                        data: 'DemoPublication',
                        label: 'DemoPublication'
                    } ),
                    new OO.ui.MenuOptionWidget( {
                        data: 'Experiment2',
                        label: 'Experiment 2'
                    } ),
                    new OO.ui.MenuOptionWidget( {
                        data: 'Experiment3',
                        label: 'Experiment 3'
                    }
                    )
                ]
            }
        } );
        this.$element.append(this.chooseExperimentDropDown.$element);
    }

    OO.ui.ChooseExperimentsWidget.prototype.getSelectedExperiment = function() {
        return this.chooseExperimentDropDown.getMenu().findSelectedItem().getData();
    }


}( OO ) );