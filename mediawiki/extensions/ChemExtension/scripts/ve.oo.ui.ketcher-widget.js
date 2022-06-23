( function ( OO ) {
    'use strict';

    ve.trackSubscribe('activity.clipboard', function(event, data) {

        if (data.action && data.action == 'paste') {
            var model = ve.init.target.getSurface().getModel();
            let alreadySeen = [];
            iterateNodesAndMakeIdUnique(model.getDocument().getDocumentNode());
            function iterateNodesAndMakeIdUnique( obj ) {
                var i;

                for ( i = 0; i < obj.children.length; i++ ) {
                    if ( obj.children[i].type == 'mwAlienInlineExtension'){

                        let id = obj.children[i].element.attributes.mw.attrs.id;
                        if (alreadySeen.indexOf(id) == -1) {
                            alreadySeen.push(id);
                        } else {
                            obj.children[i].element.attributes.mw.attrs.id = Math.random().toString(16).slice(2);
                        }
                    }

                    if ( obj.children[i].children ) {
                        iterateNodesAndMakeIdUnique( obj.children[i] );
                    }
                }
            }

        }
    });


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

    OO.ui.KetcherWidget.prototype.setData = function(formula, id) {

        let scriptPath = mw.config.get('wgScriptPath');
        let path = scriptPath + "/extensions/ChemExtension/ketcher";

        this.id = id;
        this.$element.attr("formula", formula);
        this.$element.attr("id", id);

        this.$element.attr("src", path + "/index-editor.html?id="+id+"&random="+Math.random());

    }

}( OO ) );