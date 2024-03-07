
( function () {


    OO.ui.TagsMultiSelectWidget = function MwWidgetsTagsMultiselectWidget( config ) {

        config = config || {};

        // Parent constructor
        OO.ui.TagsMultiSelectWidget.parent.call( this, $.extend( true,
            {
                clearInputOnChoose: true,
                inputPosition: 'inline',
                allowEditTags: false,
                allowArbitrary: true,
                menu: {
                    filterMode: 'substring'
                }
            },
            config

        ) );
        OO.ui.mixin.PendingElement.call( this, $.extend( true,
            {

            },
            config ));
        // Initialization

        if ( 'name' in config ) {
            // Use this instead of <input type="hidden">, because hidden inputs do not have separate
            // 'value' and 'defaultValue' properties. The script on Special:Preferences
            // (mw.special.preferences.confirmClose) checks this property to see if a field was changed.
            this.$hiddenInput = $( '<textarea>' )
                .addClass( 'oo-ui-element-hidden' )
                .attr( 'name', config.name )
                .appendTo( this.$element );
            // Update with preset values
            // Set the default value (it might be different from just being empty)
            this.$hiddenInput.prop( 'defaultValue', this.getItems().map( function ( item ) {
                return item.getData();
            } ).join( '\n' ) );
            this.on( 'change', function ( items ) {
                this.$hiddenInput.val( items.map( function ( item ) {
                    return item.getData();
                } ).join( '\n' ) );
                // Trigger a 'change' event as if a user edited the text
                // (it is not triggered when changing the value from JS code).
                this.$hiddenInput.trigger( 'change' );
            }.bind( this ) );
        }
    };

    /* Setup */

    OO.inheritClass( OO.ui.TagsMultiSelectWidget, OO.ui.MenuTagMultiselectWidget );
    OO.mixinClass( OO.ui.TagsMultiSelectWidget, OO.ui.mixin.PendingElement );

    /* Methods */

    /**
     * @inheritdoc
     */
    var handle;
    var lastRequest = null;

    OO.ui.TagsMultiSelectWidget.prototype.onInputChange = function (text) {

        let widget = this;
        let ajax = new window.ChemExtension.AjaxEndpoints();

        if (handle) {
            clearTimeout(handle);
        }
        if (lastRequest) {
            lastRequest.abort();
        }

        handle = setTimeout(function() {
            widget.pushPending();

            lastRequest = ajax.searchForTags(text).done((response) => {
                let data = response.pfautocomplete;

                widget.menu.clearItems();
                let options = [];
                let option = new OO.ui.TagsMenuOptionWidget({
                    data: text+";",
                    label: text

                });
                options.push(option);
                for(let i = 0; i < data.length; i++) {
                    option = new OO.ui.TagsMenuOptionWidget({
                        data: data[i].label+"; "+data[i].ontology,
                        label: new OO.ui.HtmlSnippet('<div class="ce-menu-item-annotation">' +
                            '<div>'+data[i].label+'</div>'
                            +'<div>'+data[i].ontology+'</div>'
                            +'</div>')

                    });
                    options.push(option);
                }
                widget.menu.addItems( options );
            }).always(() => {
                widget.popPending();
                OO.ui.TagsMultiSelectWidget.parent.prototype.onInputChange.call( widget );
                lastRequest = null;
            });
        }, 300);



    };

    OO.ui.TagsMenuOptionWidget = function MwWidgetsNamespacesMenuOptionWidget( config ) {
        // Parent
        OO.ui.TagsMenuOptionWidget.parent.call( this, config );
        this.data = config.data;
    };

    /* Setup */

    OO.inheritClass( OO.ui.TagsMenuOptionWidget, OO.ui.MenuOptionWidget );

    OO.ui.TagsMenuOptionWidget.prototype.getLabel = function () {
        return this.data;
    };
    /**
     * @inheritdoc
     */
    OO.ui.TagsMenuOptionWidget.prototype.getMatchText = function () {
        return this.getLabel();
    };


}() );
