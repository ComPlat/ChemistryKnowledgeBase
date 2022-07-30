( function ( OO ) {
    'use strict';

    OO.VisualEditorTools = function OoVisualEditorTools( config ) {

        // Properties
        // Initialization

    };

    OO.VisualEditorTools.prototype.extractChemFormNode = function(model, id) {
        let nodes = [];

        function getNodes(obj) {
            var i;

            for (i = 0; i < obj.children.length; i++) {
                if (obj.children[i].type == 'mwAlienInlineExtension') {
                    if (obj.children[i].element.attributes.mw.attrs.id == id) {
                        nodes.push(obj.children[i]);
                    }
                }

                if (obj.children[i].children) {
                    getNodes(obj.children[i]);
                }
            }
        }

        getNodes(model.getDocument().getDocumentNode());
        return nodes;
    }

    OO.VisualEditorTools.prototype.getNumberOfMoleculeRests = function(formula) {
        let m = formula.matchAll(/RGROUPS=/g);
        let i = 0;
        while(m.next().value) { i++; }
        return i;
    }

OO.initClass( OO.VisualEditorTools );



}( OO ) );