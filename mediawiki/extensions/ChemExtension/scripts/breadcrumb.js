(function ($) {
    'use strict';

    let initialized = false;
    let tools = new OO.VisualEditorTools();

    function initialize() {


        $('#ce-topic-element').click((e) => {
            $('.ce-content-panel').hide();
            $('.ce-filter-panel').hide();
            $('#ce-topic-content').show();
        });
        $('#ce-publication-element').click((e) => {
            $('.ce-content-panel').hide();
            $('.ce-filter-panel').hide();
            $('#ce-publication-content').show();
            $('#ce-publication-filter').show();
        });
        $('#ce-investigation-element').click((e) => {
            $('.ce-content-panel').hide();
            $('.ce-filter-panel').hide();
            $('#ce-investigation-content').show();
            $('#ce-investigation-filter').show();
        });
        $('#ce-molecule-element').click((e) => {
            $('.ce-content-panel').hide();
            $('.ce-filter-panel').hide();
            $('#ce-molecules-content').show();
            $('#ce-molecules-filter').show();
        });
        if (mw.config.get('wgPageName').indexOf('Special:FormEdit') === -1) {
            initializeNavbar();
        }
        initializePublicationFilter();
        initializeInvestigationFilter();
        initializeMoleculesFilter();
        initialized = true;
    }

    let NAVBAR_STATUS_COOKIE = mw.config.get('wgCookiePrefix') + 'mw.chem-extension.navbar-expanded';

    function initializeNavbar() {
        if (tools.getCookie(NAVBAR_STATUS_COOKIE) === 'expanded') {
            expandNavbar();
        } else {
            collapseNavbar();
        }
        $('#ce-side-panel-content-collapsed').click((e) => {
            expandNavbar();
        });
        $('#ce-side-panel-close-button').click(function() {
           collapseNavbar();
        });
    }

    function expandNavbar() {
        $('#ce-side-panel-content-collapsed').hide();
        $('#ce-side-panel-content').show();
        $('div.container-fluid div.row').attr('style', 'margin-left: 400px !important;');
        if (tools.getCookie(NAVBAR_STATUS_COOKIE) !== 'expanded') {
            tools.createCookie(NAVBAR_STATUS_COOKIE, 'expanded');
        }
    }

    function collapseNavbar() {
        $('#ce-side-panel-content-collapsed').show();
        $('#ce-side-panel-content').hide();
        $('div.container-fluid div.row').attr('style', 'margin-left: 40px !important;');
        if (tools.getCookie(NAVBAR_STATUS_COOKIE) !== 'collapsed') {
            tools.createCookie(NAVBAR_STATUS_COOKIE, 'collapsed');
        }
    }

    function initializePublicationFilter() {
        let filterInput = $('#ce-publication-filter-input');
        if (filterInput.length == 0) {
            return;
        }
        let oouiInput = OO.ui.infuse(filterInput);
        oouiInput.on('enter', () => {
            searchForPublication(oouiInput);
        });
        let handle = null;
        oouiInput.on('change', () => {
            if (handle) {
                clearTimeout(handle);
            }
            handle = setTimeout(function() {
                searchForPublication(oouiInput);
            }, 300);
        });
    }

    function searchForPublication(input) {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        input.pushPending();
        let category = mw.config.get('wgCanonicalNamespace') === 'Category' ? mw.config.get('wgTitle') : 'Topic';
        ajax.getPublications(category, input.getValue()).done((result) => {
            input.popPending();
            let list = $('#ce-publication-list');
            list.empty();
            list.append(result.html);
        }).fail(()=> {
            input.popPending();
        });
    }

    function initializeInvestigationFilter() {
        let input = OO.ui.infuse($('#ce-investigation-filter-input'));
        input.on('enter', () => {
            searchForInvestigation(input);
        });
        let handle = null;
        input.on('change', () => {
            if (handle) {
                clearTimeout(handle);
            }
            handle = setTimeout(function() {
                searchForInvestigation(input);
            }, 300);
        });
    }

    function searchForInvestigation(input) {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        input.pushPending();
        let pageTitle = mw.config.get('wgPageName');
        ajax.getInvestigations(pageTitle, input.getValue()).done((result) => {
            input.popPending();
            let list = $('#ce-investigation-list');
            list.empty();
            list.append(result.html);
        }).fail(()=> {
            input.popPending();
        });
    }

    function initializeMoleculesFilter() {
        let input = OO.ui.infuse($('#ce-molecules-filter-input'));
        input.on('enter', () => {
            searchForMolecule(input);
        });
        let handle = null;
        input.on('change', () => {
            if (handle) {
                clearTimeout(handle);
            }
            handle = setTimeout(function() {
                searchForMolecule(input);
            }, 300);
        });
    }

    function searchForMolecule(input) {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        input.pushPending();
        ajax.searchForMolecule(input.getValue(), mw.config.get('wgRelevantPageName')).done((result) => {
            input.popPending();
            let results = result.pfautocomplete;
            let list = $('#ce-molecules-list ul');
            list.empty();
            if (results.length === 0) {
                list.append('no molecules found');
                return;
            }
            for (let i = 0; i < results.length; i++) {
                let text = results[i].Trivialname !== '' ? results[i].Trivialname : results[i].IUPACName;
                if (results[i].Abbreviation !== '') {
                    text += ' (' +  results[i].Abbreviation + ')';
                }
                let a = $('<a>')
                    .attr('href', mw.config.get('wgScriptPath') + '/' + results[i].title)
                    .attr('title', results[i].title)
                    .append(text);
                list.append($('<li>').append(a));
            }
            let container = $('#ce-molecules-list');
            window.ChemExtension.initTooltips(container);

            let searchHint = $('#ce-moleculelist-search-hint');
            let a = $('<a>')
                .attr('href', mw.config.get('wgScriptPath') + '/Special:Search?search='
                    + encodeURIComponent(input.getValue()) + '&prefix='+decodeURIComponent('category=Molecule'))
                .attr('target', '_blank')
                .append('open "'+input.getValue()+'" in fulltext search');
            searchHint.empty();
            searchHint.append(a);
        }).fail(()=> {
            input.popPending();
        });
    }

    $(function () {
        if (!initialized) {
            initialize();
        }
    });

    mw.hook('postEdit').add(function () {
        if (!initialized) {
            initialize();
        }
    });

}(jQuery));