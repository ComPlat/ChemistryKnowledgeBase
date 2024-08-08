
( function () {


    OO.ui.RedoxMultiSelectWidget = function MwWidgetsRedoxMultiSelectWidget( config ) {

        config = config || {};

        // Parent constructor
        OO.ui.RedoxMultiSelectWidget.parent.call( this, $.extend( true,
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

    OO.inheritClass( OO.ui.RedoxMultiSelectWidget, OO.ui.MenuTagMultiselectWidget );
    OO.mixinClass( OO.ui.RedoxMultiSelectWidget, OO.ui.mixin.PendingElement );

    /* Methods */

    OO.ui.RedoxMultiSelectWidget.prototype.addTagFromInput = function () {
        var tagInfo = this.getTagInfoFromInput();

        if ( !tagInfo.data ) {
            return;
        }

        let numberPattern = /^[+-]?[0-9]*\.?[0-9]+?\*?(,[+-]?[0-9]*\.?[0-9]+?\*?)*$/;
        if (tagInfo.label.match(numberPattern)) {
            if ( this.addTag( tagInfo.data, tagInfo.label ) ) {
                this.clearInput();
            }
        }

    }

    OO.ui.RedoxMenuOptionWidget = function MwWidgetsNamespacesMenuOptionWidget( config ) {
        // Parent
        OO.ui.RedoxMenuOptionWidget.parent.call( this, config );
        this.data = config.data;
    };

    /* Setup */

    OO.inheritClass( OO.ui.RedoxMenuOptionWidget, OO.ui.MenuOptionWidget );

    OO.ui.RedoxMenuOptionWidget.prototype.getLabel = function () {
        return this.data;
    };
    /**
     * @inheritdoc
     */
    OO.ui.RedoxMenuOptionWidget.prototype.getMatchText = function () {
        return this.getLabel();
    };


}() );
