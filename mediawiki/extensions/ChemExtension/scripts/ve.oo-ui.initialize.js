(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length === 0) return;
        experimentList.each( (i,e) => OO.ui.infuse(e));
    }

    $(function() {
        initialize();
    });

    mw.hook( 'postEdit' ).add(function() {
        initialize();
    });

}(jQuery));