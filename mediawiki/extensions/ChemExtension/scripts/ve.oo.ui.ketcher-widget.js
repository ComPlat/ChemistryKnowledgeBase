( function ( OO ) {
    'use strict';


    OO.ui.KetcherWidget = function OoUiKetcherWidget( config ) {
        // Configuration initialization
        config = config || {};

        // Parent constructor
        OO.ui.KetcherWidget.super.call( this, config );

        // Properties
        this.input = config.input;

        // Initialization

        this.$element.attr("class", "ketcher-editor")
        this.$element.attr("height", "560px");
    };

    /* Setup */

    OO.inheritClass( OO.ui.KetcherWidget, OO.ui.Widget );

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.KetcherWidget.static.tagName = 'iframe';

    OO.ui.KetcherWidget.prototype.setData = function(formula, smiles) {

        let scriptPath = mw.config.get('wgScriptPath');
        let path = scriptPath + "/extensions/ChemExtension/ketcher";

        this.$element.attr("formula", formula);
        this.$element.attr("smiles", smiles);
        this.$element.attr("src", path + "/index-editor.html?random="+Math.random());

    }

}( OO ) );