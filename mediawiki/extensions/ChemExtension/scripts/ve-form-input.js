(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length === 0) return;
        OO.ui.infuse(experimentList);
    }

    $(function() {
        initialize();
    });

    mw.hook( 'postEdit' ).add(function() {
        initialize();
    });

}(jQuery));