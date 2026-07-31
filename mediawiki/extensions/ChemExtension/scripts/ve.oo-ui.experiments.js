(function ($) {
    'use strict';


    function initialize() {
        let experimentList = $('.experimentlist');
        if (experimentList.length > 0) {
            experimentList.each( (i,e) => OO.ui.infuse(e));
        }

        let toggleGroupHeader = (e) => {
            let target = $(e.target);
            let groups = target.attr('class').split(/\s+/).filter((e) => e.startsWith('group_'));
            if (groups.length === 0) return;
            let group = groups[0];
            // hides all columns in the group
            $('th.'+group).trigger('dblclick');
        };
        let addGroupHeader = function ($table, experimentType) {
            const $headerCells = $table.find('th');

            // Build the new group header row
            const $tr = $('<tr>').addClass('inv_group_header');
            $tr.append($('<td>')); // leading empty cell

            const columnSpans = {};
            const groupHeaders = Object.keys(experimentType);

            groupHeaders.forEach((gh) => {
                // Count how many <th> cells have a class containing this group name
                const num = $headerCells.filter(function () {
                    return ($(this).attr('class') || '').indexOf(gh) !== -1;
                }).length;

                if (num > 0) {
                    const $td = $('<td>')
                        .attr('colspan', num)
                        .addClass('inv_group_header')
                        .addClass(gh)
                        .text(experimentType[gh]);
                    $tr.append($td);
                }

                columnSpans[gh] = num;
            });

            // Insert the new group header row before the first existing row
            const $rows = $table.find('tr');
            const $firstRow = $rows.first();
            $firstRow.before($tr);

            // Apply group class to each <td> in subsequent rows
            $rows.slice(1).each(function () {
                const $columns = $(this).find('td');
                let j = 0;
                groupHeaders.forEach((gh) => {
                    for (let i = 0; i < columnSpans[gh]; i++) {
                        $columns.eq(j).attr('class', gh);
                        j++;
                    }
                });
            });
        }

        let collapseOrExpandColumns = (e) => {

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

        // highlight literature-references
        let highlightLiteratureReferences = function(table) {
            table.find('span.literature-link a').click((e) => {
                let target = $(e.target);
                let href = target.attr('href');
                $('.chem_ext_literature').css({'font-weight': 'normal'});
                $(href).css({'font-weight': 'bold'});
            });
        }

        let updateTitles = function(table){
            // makes tables sortable
            table.find('thead th').each((i, e) => {
                $(e).attr('title', $(e).attr('title-copy') );
            });
        };

        function initTable(e) {
            let target = $(e);
            let f = target.find('> tbody > tr:first-child', target);
            let head = $('<thead>').insertBefore(target.find('> tbody')).append(f);
            head.find('th').each((i, e) => {
                $(e).attr('title-copy', $(e).attr('title'));
            });
            let experimentType = target.attr('about');
            if (experimentType) {
                let exp = mw.config.values['experiments'][experimentType];
                if (exp) {
                    let headerGroups = exp['headerGroups'];
                    console.log(headerGroups);
                    addGroupHeader(target, headerGroups || {});

                }

            }
            target.tablesorter();
            updateTitles(target);
            highlightLiteratureReferences(target);
            target.bind("sortEnd.tablesorter", function () {
                updateTitles(target);
            });

            $('td.inv_group_header', target).click(toggleGroupHeader);
            $('th', target).off('dblclick');
            $('th', target).dblclick(collapseOrExpandColumns);
        }

        $('table.experiment-link, table.experiment-list').each(function(i,e) {
            initTable(e);
        });

        // buttons for experiments
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
                initTable(newNode.find('table'));
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