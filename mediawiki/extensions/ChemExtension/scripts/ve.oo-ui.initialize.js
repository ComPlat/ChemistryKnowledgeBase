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

    function initializeDOIInfoBoxToggle() {
        $('.infobox th').click((e) => {
            let table = $(e.target).closest('table');
            $('tr', table).slice(1).toggle();
        });
    }

    $(function() {
        initialize();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
    });

    mw.hook( 'postEdit' ).add(function() {
        initialize();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
    });

}(jQuery));