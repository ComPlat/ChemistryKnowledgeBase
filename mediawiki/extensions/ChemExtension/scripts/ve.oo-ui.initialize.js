(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length === 0) return;
        experimentList.each( (i,e) => OO.ui.infuse(e));
    }

    function initializeToggleBoxes() {
        $('.toggle-box').click((e) => {
            let target = $(e.target).attr('resource');
            $('#'+target).toggle();
        });
    }

    $(function() {
        initialize();
        initializeToggleBoxes();
    });

    mw.hook( 'postEdit' ).add(function() {
        initialize();
    });

}(jQuery));