(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length > 0) {
            experimentList.each( (i,e) => OO.ui.infuse(e));
        }

        $('table.wikitable th').click((e) => {
            let th = $(e.target);
            let collapsed = (th.attr('collapsed') === 'true');
            th.attr('collapsed', !collapsed);
            let table = th.closest('table');
            let columns = table.find('tr td:nth-child(' + whichChild(th) + ')', table);
            if (collapsed) {
                th.empty().append(th.attr('stashed'));
                columns.removeClass('collapsed-column');
                columns.each((i, e) => {
                    let el = $(e);
                    el.append(el.attr('stashed'));
                });
            } else {
                th.attr('stashed', th.html());
                columns.each((i, e) => {
                    let el = $(e);
                    el.attr('stashed', el.html());
                });
                th.empty().append('.');
                columns.addClass('collapsed-column');
                columns.empty();
            }
        });

        $('span.experiment-link-show-button').click(function(e) {
            let buttonLabel = $(e.target);
            let button = buttonLabel.closest('span.experiment-link-show-button');
            let id = button.attr('id');
            let table = $('#'+id+'-table').find('table');
            let visible = table.is(':visible');
            if (visible) {
                buttonLabel.text('Show table');
                table.hide();
            } else {
                buttonLabel.text('Hide table');
                table.show();

            }
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

    function intializeExpandNavigationButton() {
        let expanded = false;
        $('#ce-side-panel-expand-button').click(()=> {
           $('#ce-side-panel-content').css({width: expanded ? '400px' : '1000px'});
           expanded = !expanded;
        });
    }

    $(function() {
        initialize();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
        intializeExpandNavigationButton();
    });

    mw.hook( 'postEdit' ).add(function() {
        initialize();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
    });

}(jQuery));