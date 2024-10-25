(function ($) {
    'use strict';

    function initializeMolecules() {

        $('span.ce-moleculelink-show').click((e) => {
            let target = $(e.target);
            target.siblings('a').find('span.ce-moleculelink-image').show();
            target.remove();
        });


    }

    function initializeAnnotationTooltips() {
        $('span.ce-annotation').each((i, e) => {
            let annotationEl = $(e);
            let data = annotationEl.attr('resource');
            let html = data.split(',').map((a) => { return '<li>'+a+'</li>'; });
            annotationEl.qtip({
                content: "<div class='ce-annotation-tooltip'><ul>"+html+"</ul></div>",
                style: {},
                position: {
                    viewport: $(window)
                }
            });

        });
    }

    function initializeToggleBoxes() {
        $('.toggle-box').off('click');
        $('.toggle-box').click((e) => {
            let target = $(e.target).attr('resource');
            $('#'+target).toggle();
        });
    }

    function initializeDOIInfoBoxToggle() {
        $('.doiinfobox th').off('click');
        $('.doiinfobox th').click((e, action) => {
            let table = $(e.target).closest('table').next();
            let rows = $('tr', table);
            if (rows.eq(0).is(':visible') || action === 'close') {
                table.css({
                    position: 'relative',
                    top: "0px",
                    left: "0px"
                });
                rows.hide();
            } else {
                let position = table.position();
                table.css({
                    position: 'absolute',
                    top: position.top+"px",
                    left: position.left+"px"
                });
                rows.show();
            }
        });
    }

    function initializeMoleculeInfoBoxToggle() {
        $('.molecule-infobox th').off('click');
        $('.molecule-infobox th').click((e) => {
            let table = $(e.target).closest('table');
            let rows = $('tr:not(:first)', table);
            rows.toggle();
        });
    }

    function initializeExpandNavigationButton() {
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
        initializeMolecules();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
        initializeExpandNavigationButton();
        initializeRGroups();
        initializeAnnotationTooltips();
        initializeMoleculeInfoBoxToggle();
    });

    mw.hook( 've.activationComplete' ).add(function() {
        $('#cancelve').show();

    });

    $('#cancelve').hide();
    mw.hook( 've.deactivationComplete' ).add(function() {
        window.location.reload();
    });
    mw.hook( 'postEdit' ).add(function() {
        initializeMolecules();
        initializeToggleBoxes();
        initializeDOIInfoBoxToggle();
        $('#cancelve').hide();
    });

}(jQuery));