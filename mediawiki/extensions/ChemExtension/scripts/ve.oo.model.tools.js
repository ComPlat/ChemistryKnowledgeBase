( function ( OO ) {
    'use strict';

    OO.VisualEditorTools = function OoVisualEditorTools( config ) {

        // Properties
        // Initialization

    };

    OO.VisualEditorTools.prototype.newID = function() {
        return Math.random().toString(16).slice(2);
    }

    OO.VisualEditorTools.prototype.getNumberOfMoleculeRests = function(formula) {
        let m = formula.matchAll(/RGROUPS=/g);
        let i = 0;
        while(m.next().value) { i++; }
        return i;
    }

OO.initClass( OO.VisualEditorTools );



}( OO ) );