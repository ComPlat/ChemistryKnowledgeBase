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

    OO.VisualEditorTools.prototype.getRestIds = function(formula) {
        let m = formula.matchAll(/RGROUPS=\((\d+)\s*(\d+)/g);
        let restIds = [];
        let next = m.next().value;
        while(next) {
            if (restIds.indexOf("r"+next[2]) === -1) {
                restIds.push("r" + next[2]);
            }
            next = m.next().value;
        }

        return restIds.sort();
    }

    OO.VisualEditorTools.prototype.removeAllNonExistingRests = function(attrs, restIds) {
        for(let a in attrs) {
            if (a.match(/r\d+/) && restIds.indexOf(a) === -1) {
                delete attrs[a];
            }
        }
    }

OO.initClass( OO.VisualEditorTools );



}( OO ) );