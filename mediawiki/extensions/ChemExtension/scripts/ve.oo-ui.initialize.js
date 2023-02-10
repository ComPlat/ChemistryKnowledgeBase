(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length > 0) {
            experimentList.each( (i,e) => OO.ui.infuse(e));
        }

        $('th[property=hidden]').click((e) => {
            let th = $(e.target).empty();
            th.append(th.attr('about'));
            let table = th.closest('table');
            table.find('tr td:nth-child('+whichChild(th)+')', table).removeClass('collapsed-column');
        });
    }

    function whichChild(node) {
        let i = 1;
        while(node = node.prev()) {
            if (node.length === 0) {
                break;
            }
            i++;
        }
        return i;
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