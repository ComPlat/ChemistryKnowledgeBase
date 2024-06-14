(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length > 0) {
            experimentList.each( (i,e) => OO.ui.infuse(e));
        }

        $('table.wikitable:not(.infobox) th').off('dblclick');
        $('table.wikitable:not(.infobox) th').dblclick((e) => {

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
                window.ChemExtension.initTooltips();
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

            setTimeout(function() {
                th.trigger('click'); // hack to reset sort state
            }, 10);
        });

        // make tables sortable
        let updateTitles = function(table){

            table.find('thead th').each((i, e) => {
                $(e).attr('title', $(e).attr('title-copy') );
            });
        };
        $('table.experiment-link, table.experiment-list').each(function(i,e) {
           let target = $(e);
           let f = target.find('> tbody > tr:first-child', target);
            let head = $('<thead>').insertBefore(target.find('> tbody')).append(f);
            head.find('th').each((i, e) => {
                $(e).attr('title-copy', $(e).attr('title') );
            });
            target.tablesorter();
            updateTitles(target);
            target.bind("sortEnd.tablesorter",function() {
                updateTitles(target);
            });
        });


        let toggleExperimentHandler = function(e) {
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
                window.ChemExtension.NavBar.collapseNavbar();

            }
        };

        $('span.experiment-link-show-button').off('click');
        $('span.experiment-link-show-button').click(toggleExperimentHandler);

        let refreshExperimentHandler = function(e) {
            let buttonLabel = $(e.target);
            buttonLabel.text('Refreshing...');
            let ajax = new window.ChemExtension.AjaxEndpoints();
            ajax.invalidateInvestigationCache($(e.target).closest('button').attr('value')).done((response) => {
                mw.notify('Cache invalidated');

                let experimentContainer = $(e.target).closest('.experiment-link-border');
                let table = experimentContainer.find('table');
                let visible = table.is(':visible');

                let newNode = $(response.html);
                experimentContainer.replaceWith(newNode);
                newNode.find('span.experiment-link-show-button').click(toggleExperimentHandler);
                newNode.find('span.experiment-link-refresh-button').click(refreshExperimentHandler);
                if (visible) {
                    newNode.find('table').show();
                }
            }).fail((e) => {
                mw.notify('Cache invalidation failed');
            });
        };
        $('span.experiment-link-refresh-button').off('click');
        $('span.experiment-link-refresh-button').click(refreshExperimentHandler);

        $('.experiment-link-help').qtip({
            content: "<ul class='experiment-help-bullets'>" +
                "<li>double click on table header for showing/hiding columns</li>" +
                "<li>single click on table header for sorting columns</li>" +
                "</ul>",
            style: {},
            position: {
                viewport: $(window)
            }
        });

        $('.experiment-help').qtip({
            content: "<ul class='experiment-help-bullets'>" +
                "<li>double click on table header for showing/hiding columns</li>" +
                "<li>single click on table header for sorting columns</li>" +
                "<li>include column specifies if the experiment should be included on topic pages</li>" +
                "</ul>",
            style: {},
            position: {
                viewport: $(window)
            }
        });

        // highlight literature-references
        $('.experiment-link, span.literature-link a').click((e) => {
            let target = $(e.target);
            let href = target.attr('href');
            $('.chem_ext_literature').css({'font-weight': 'normal'});
            $(href).css({'font-weight': 'bold'});
        });

        $('span.ce-moleculelink-show').click((e) => {
            let target = $(e.target);
            target.siblings('a').find('span.ce-moleculelink-image').show();
            target.remove();
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
        $('.infobox th').off('click');
        $('.infobox th').click((e, action) => {
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
        initializeAnnotationTooltips();
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