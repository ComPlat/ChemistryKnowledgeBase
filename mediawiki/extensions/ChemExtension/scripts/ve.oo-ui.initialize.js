(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length > 0) {
            experimentList.each( (i,e) => OO.ui.infuse(e));
        }

        $('table.wikitable:not(.infobox) th').off('click');
        $('table.wikitable:not(.infobox) th').click((e) => {
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

        $('span.experiment-link-show-button').off('click');
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

        $('#toc').prepend($('table.infobox'));
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
        $('.toggle-box').off('click');
        $('.toggle-box').click((e) => {
            let target = $(e.target).attr('resource');
            $('#'+target).toggle();
        });
    }

    function initializeDOIInfoBoxToggle() {
        $('.infobox th').off('click');
        $('.infobox th').click((e) => {
            let table = $(e.target).closest('table');
            $('tr', table).slice(1).toggle();
        });
    }

    function intializeExpandNavigationButton() {
        let expanded = false;
        $('#ce-side-panel-expand-button').off('click');
        $('#ce-side-panel-expand-button').click(()=> {
           $('#ce-side-panel-content').css({width: expanded ? '400px' : '1000px'});
           expanded = !expanded;
        });
    }

    function initializeRGroups() {
        $('.rgroups-button').off('click');
        $('.rgroups-button').click((e)=> {
            let target = $(e.target);
            let moleculeKey = target.attr('moleculekey');
            let pageid = target.attr('pageid');

            let draggable = $('<div>').addClass('ui-widget-content rgroup-draggable');
            let myDialog = new window.parent.ChemExtension.ShowGroupsDialog( {
                size: 'large'
            }, draggable );
            myDialog.initialize({moleculeKey: moleculeKey, pageid: pageid});

            draggable.css({
                top: getScrollPos() + Math.floor((window.parent.innerHeight - 450) / 2),
                left: Math.floor((window.parent.innerWidth - 1000) / 2)
            });
            draggable.draggable();
            ;
            $('body').prepend($('<div>').height('0px').append(draggable));
        });
    }

    function getScrollPos() {

        let doc = window.document.documentElement;
        return (window.pageYOffset || doc.scrollTop)  - (doc.clientTop || 0);

    }

    $(function() {
        initialize();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
        intializeExpandNavigationButton();
        initializeRGroups();
    });

    mw.hook( 've.activationComplete' ).add(function() {
        $('#cancelve').show();

    });

    $('#cancelve').hide();
    mw.hook( 've.deactivationComplete' ).add(function() {
        window.location.reload();
    });
    mw.hook( 'postEdit' ).add(function() {
        initialize();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
        $('#cancelve').hide();
    });

}(jQuery));