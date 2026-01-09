(function ($) {
    'use strict';

    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length > 0) {
            experimentList.each( (i,e) => OO.ui.infuse(e));
        }

        let toggleHeaderLine = (e) => {
            let target = $(e.target);
            let groups = target.attr('class').split(/\s+/).filter((e) => e.startsWith('group_'));
            if (groups.length === 0) return;
            let group = groups[0];
            $('th.'+group).trigger('dblclick');
        };
        $('table.wikitable:not(.infobox) td.inv_group_header').click(toggleHeaderLine);

        $('table.wikitable:not(.infobox) th').off('dblclick');
        let headerDblClick = (e) => {

            let th = $(e.target);
            let collapsed = (th.attr('collapsed') === 'true');
            th.attr('collapsed', !collapsed);
            let table = th.closest('table');
            let columns = table.find('tr:not(.inv_group_header) td:nth-child(' + whichChild(th) + ')', table);
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
        };
        $('table.wikitable:not(.infobox) th').dblclick(headerDblClick);

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

        let refreshExperimentLinkHandler = function(e) {
            let buttonLabel = $(e.target);
            buttonLabel.text('Refreshing...');
            let ajax = new window.ChemExtension.AjaxEndpoints();
            ajax.invalidateInvestigationLinkCache($(e.target).closest('button').attr('value')).done((response) => {
                mw.notify('Cache invalidated');

                let experimentContainer = $(e.target).closest('.experiment-link-border');
                let table = experimentContainer.find('table');
                let visible = table.is(':visible');

                let newNode = $(response.html);
                experimentContainer.replaceWith(newNode);
                newNode.find('span.experiment-link-show-button').click(toggleExperimentHandler);
                newNode.find('span.experiment-link-refresh-button').click(refreshExperimentLinkHandler);
                newNode.find('td.inv_group_header').click(toggleHeaderLine);
                newNode.find('th').dblclick(headerDblClick);
                if (visible) {
                    newNode.find('table').show();
                }
            }).fail((e) => {
                mw.notify('Cache invalidation failed');
            });
        };
        $('span.experiment-link-refresh-button').off('click');
        $('span.experiment-link-refresh-button').click(refreshExperimentLinkHandler);

        let refreshExperimentListHandler = function(e) {
            let buttonLabel = $(e.target);
            buttonLabel.text('Refreshing...');
            let ajax = new window.ChemExtension.AjaxEndpoints();
            ajax.invalidateInvestigationListCache($(e.target).closest('button').attr('value')).done((response) => {
                mw.notify('Cache invalidated');

                window.location.reload();
            }).fail((e) => {
                mw.notify('Cache invalidation failed');
            });
        };
        $('span.experiment-list-refresh-button').off('click');
        $('span.experiment-list-refresh-button').click(refreshExperimentListHandler);


        let exportExperimentHandler = function(e) {
            let buttonLabel = $(e.target);
            buttonLabel.text('Exporting...');
            let ajax = new window.ChemExtension.AjaxEndpoints();
            ajax.exportExperiment($(e.target).closest('button').attr('value')).done(() => {
                buttonLabel.text('Export');
                mw.notify('Export successful.');
            }).fail((e) => {
                buttonLabel.text('Export');
                mw.notify('Export failed');
            });
        };
        $('span.experiment-link-export-button').off('click');
        $('span.experiment-link-export-button').click(exportExperimentHandler);

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

        $('span.experiment-list-rename-button').off('click');
        $('span.experiment-list-rename-button').click((e) => {
            let button = $(e.target).closest('button');
            let request = JSON.parse(button.attr('value'));
            let ajax = new window.ChemExtension.AjaxEndpoints();
            OO.ui.prompt('Please enter new name of investigation').done((result) => {
                if (!result) return;
                ajax.renamePage(request.page+"/"+request.investigationName, request.page+"/"+result)
                    .done(() => {
                        mw.notify("Investigation renamed")})
                    .catch((e) => {
                        mw.notify("Investigation renaming FAILED! Reason: "+e.responseText);
                    });
            });

        });

        // highlight literature-references
        $('.experiment-link, span.literature-link a').click((e) => {
            let target = $(e.target);
            let href = target.attr('href');
            $('.chem_ext_literature').css({'font-weight': 'normal'});
            $(href).css({'font-weight': 'bold'});
        });

        checkErrorsPeriodically();
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

    function checkErrorsPeriodically() {

        setTimeout(() => {
            $('span.error').each((i, e) => {
                let target = $(e);
                let dataJson = target.attr('resource');
                if (!dataJson || dataJson === '') return;
                let data = JSON.parse(dataJson);
                switch (data.code) {
                    case 1001: // experiment not exists
                        let tools = new OO.VisualEditorTools();
                        tools.refreshVENode((node) => {
                            if (node.type === 'mwTransclusionBlock' || node.type === 'mwTransclusionInline') {
                                let params = node.model.element.attributes.mw.parts[0].template.params;
                                return (params.name && params.name.wt == data.experimentName);
                            }
                        });
                        break;
                }
            });
            checkErrorsPeriodically();
        }, 10 * 1000)
    }


    $(function() {
        initialize();
    });

}(jQuery));