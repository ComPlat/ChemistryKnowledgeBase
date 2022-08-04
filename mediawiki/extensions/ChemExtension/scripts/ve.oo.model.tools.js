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

    OO.VisualEditorTools.prototype.removeAllRests = function(attrs) {
        for(let i = 1; i < 100; i++) {
            if (attrs['r'+i]) {
                delete attrs['r'+i];
            }
        }
    }

OO.initClass( OO.VisualEditorTools );



}( OO ) );