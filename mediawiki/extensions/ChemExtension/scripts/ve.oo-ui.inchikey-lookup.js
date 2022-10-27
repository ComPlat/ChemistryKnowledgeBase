( function ( OO ) {
    'use strict';
    /**
     * OO.ui for InchiKey lookup.
     *
     * @class
     * @extends OO.ui.TextInputWidget
     * @mixins OO.ui.mixin.LookupElement
     *
     * @constructor
     * @param {Object} config Configuration options
     */
    OO.ui.InchiKeyLookupTextInputWidget = function InchiKeyLookupTextInputWidget( config ) {
        // Parent constructor
        OO.ui.TextInputWidget.call( this, $.extend( { validate: 'string' }, config ) );
        // Mixin constructors
        OO.ui.mixin.LookupElement.call( this, config );

    };
    OO.inheritClass( OO.ui.InchiKeyLookupTextInputWidget, OO.ui.TextInputWidget );
    OO.mixinClass( OO.ui.InchiKeyLookupTextInputWidget, OO.ui.mixin.LookupElement );

    var handle;
    var deferred = $.Deferred();

    /**
     * @inheritdoc
     */
    OO.ui.InchiKeyLookupTextInputWidget.prototype.getLookupRequest = function () {
        let value = this.getValue();

        if (deferred) {
            deferred.reject();
            deferred = $.Deferred();
        }
        if (handle) {
            clearTimeout(handle);
            handle = null;
        }

        handle = setTimeout(function() {
            let valueLower = value.toLowerCase();
            let ajax = new window.ChemExtension.AjaxEndpoints();
            ajax.searchForMolecule(valueLower).then((response) => {
                deferred.resolve( response.results );
            }).catch((e) => {
                deferred.resolve( [] );
            })
        }, 300);

        return deferred.promise( { abort: function () {} } );
    };
    /**
     * @inheritdoc
     */
    OO.ui.InchiKeyLookupTextInputWidget.prototype.getLookupCacheDataFromResponse = function ( response ) {
        return response || [];
    };
    /**
     * @inheritdoc
     */
    OO.ui.InchiKeyLookupTextInputWidget.prototype.getLookupMenuOptionsFromData = function ( data ) {
        var
            items = [],
            i;
        for ( i = 0; i < data.length; i++ ) {
            let labelToShow = data[ i ].Trivialname;
            if (data[i].CAS != '') labelToShow += ", "+data[i].CAS;
            if (data[i].IUPACName != '') labelToShow += ", "+data[i].IUPACName;
            items.push( new OO.ui.MenuOptionWidget( {
                data: data[i].InChIKey,
                label: labelToShow
            } ) );
        }
        return items;
    };
}( OO ) );