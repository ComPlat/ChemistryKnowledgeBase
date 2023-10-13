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
        initAvailableRGroups();
    };
    OO.inheritClass(OO.ui.RGroupsLookupTextInputWidget, OO.ui.TextInputWidget);
    OO.mixinClass(OO.ui.RGroupsLookupTextInputWidget, OO.ui.mixin.LookupElement);

    var handle;
    var deferred = $.Deferred();
    var availableRGroups;

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
            let result = $.grep(availableRGroups, function (e) {
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

    function initAvailableRGroups() {
        let rGroups = getWithExpiry('ChemExtension.AvailableRGroups');
        if (rGroups != null) {
            availableRGroups = rGroups;
            return;
        }
        // fallback, just in case
        availableRGroups = window.ChemExtension.RGroups;

        // then request new
        let ajax = new window.ChemExtension.AjaxEndpoints();
        ajax.getAvailableRGroups().done((response) => {
            setWithExpiry('ChemExtension.AvailableRGroups', response.rgroups, 3600 * 1000);
            availableRGroups = response.rgroups;
        }).fail(() => {
            console.log('Error requesting available RGroups. Use default set');
        });
    }

    function setWithExpiry(key, value, ttl) {
        const now = new Date()

        // `item` is an object which contains the original value
        // as well as the time when it's supposed to expire
        const item = {
            value: value,
            expiry: now.getTime() + ttl,
        }
        localStorage.setItem(key, JSON.stringify(item))
    }

    function getWithExpiry(key) {
        const itemStr = localStorage.getItem(key)
        // if the item doesn't exist, return null
        if (!itemStr) {
            return null
        }
        const item = JSON.parse(itemStr)
        const now = new Date()
        // compare the expiry time of the item with the current time
        if (now.getTime() > item.expiry) {
            // If the item is expired, delete the item from storage
            // and return null
            localStorage.removeItem(key)
            return null
        }
        return item.value
    }

}(OO));