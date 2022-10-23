( function ( OO ) {
    'use strict';
/**
 * OO.ui for RGroups lookup.
 *
 * @class
 * @extends OO.ui.TextInputWidget
 * @mixins OO.ui.mixin.LookupElement
 *
 * @constructor
 * @param {Object} config Configuration options
 */
OO.ui.RGroupsLookupTextInputWidget = function RGroupsLookupTextInputWidget( config ) {
    // Parent constructor
    OO.ui.TextInputWidget.call( this, $.extend( { validate: 'string' }, config ) );
    // Mixin constructors
    OO.ui.mixin.LookupElement.call( this, config );
};
OO.inheritClass( OO.ui.RGroupsLookupTextInputWidget, OO.ui.TextInputWidget );
OO.mixinClass( OO.ui.RGroupsLookupTextInputWidget, OO.ui.mixin.LookupElement );
/**
 * @inheritdoc
 */
OO.ui.RGroupsLookupTextInputWidget.prototype.getLookupRequest = function () {
    var
        value = this.getValue(),
        deferred = $.Deferred(),
        handle;

    this.getValidity().then( function () {

        let valueLower = value.toLowerCase();
        if (handle) clearTimeout(handle);
        handle = setTimeout(function() {
            let result = $.grep(window.ChemExtension.RGroups, function(e) {
                return e.data.indexOf(valueLower) > -1;
            });
            deferred.resolve( result );
        }, 300);

    }, function () {
        // No results when the input contains invalid content
        deferred.resolve( [] );
    } );
    return deferred.promise( { abort: function () {} } );
};
/**
 * @inheritdoc
 */
OO.ui.RGroupsLookupTextInputWidget.prototype.getLookupCacheDataFromResponse = function ( response ) {
    return response || [];
};
/**
 * @inheritdoc
 */
OO.ui.RGroupsLookupTextInputWidget.prototype.getLookupMenuOptionsFromData = function ( data ) {
    var
        items = [],
        i;
    for ( i = 0; i < data.length; i++ ) {

        items.push( new OO.ui.MenuOptionWidget( {
            data: data[ i ].label,
            label: data[ i ].label
        } ) );
    }
    return items;
};
}( OO ) );