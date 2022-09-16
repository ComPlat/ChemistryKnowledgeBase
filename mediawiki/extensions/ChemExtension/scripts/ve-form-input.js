(function ($) {
    'use strict';

    function initialize() {
        let veFormInputs = $('.veforminput');
        if (veFormInputs.length === 0) return;
        OO.ui.infuse(veFormInputs);
    }

    $(function() {
        initialize();
    });

    mw.hook( 'postEdit' ).add(function() {
        initialize();
    });

}(jQuery));