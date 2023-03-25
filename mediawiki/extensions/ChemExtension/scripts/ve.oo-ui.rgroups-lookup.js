(function (OO) {
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
    OO.ui.RGroupsLookupTextInputWidget = function RGroupsLookupTextInputWidget(config) {
        // Parent constructor
        OO.ui.TextInputWidget.call(this, $.extend({validate: 'string'}, config));
        // Mixin constructors
        OO.ui.mixin.LookupElement.call(this, config);
    };
    OO.inheritClass(OO.ui.RGroupsLookupTextInputWidget, OO.ui.TextInputWidget);
    OO.mixinClass(OO.ui.RGroupsLookupTextInputWidget, OO.ui.mixin.LookupElement);

    var handle;
    var deferred = $.Deferred();

    /**
     * @inheritdoc
     */
    OO.ui.RGroupsLookupTextInputWidget.prototype.getLookupRequest = function () {
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
            let result = $.grep(window.ChemExtension.RGroups, function (e) {
                return e.data.indexOf(valueLower) > -1;
            });
            result = result.sort((a,b) => {
                return a.label.length - b.label.length;
            });
            deferred.resolve( result );
        }, 300);

        return deferred.promise( { abort: function () {} } );

    };
    /**
     * @inheritdoc
     */
    OO.ui.RGroupsLookupTextInputWidget.prototype.getLookupCacheDataFromResponse = function (response) {
        return response || [];
    };
    /**
     * @inheritdoc
     */
    OO.ui.RGroupsLookupTextInputWidget.prototype.getLookupMenuOptionsFromData = function (data) {
        var
            items = [],
            i;
        for (i = 0; i < data.length; i++) {

            items.push(new OO.ui.MenuOptionWidget({
                data: data[i].label,
                label: data[i].label
            }));
        }
        return items;
    };
}(OO));