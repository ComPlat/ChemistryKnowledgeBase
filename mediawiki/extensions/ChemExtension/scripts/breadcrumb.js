(function ($) {
    'use strict';

    let initialized = false;

    function initialize() {


        $('#ce-topic-switch').click((e) => {
            $('.ce-content-panel').hide();
            $('#ce-topic-content').show();
        });
        $('#ce-publication-switch').click((e) => {
            $('.ce-content-panel').hide();
            $('#ce-publication-content').show();
        });
        $('#ce-investigation-switch').click((e) => {
            $('.ce-content-panel').hide();
            $('#ce-investigation-content').show();
        });
        $('#ce-molecules-switch').click((e) => {
            $('.ce-content-panel').hide();
            $('#ce-molecules-content').show();
        });
        initializePublicationFilter();
        initializeMoleculesFilter();
        initialized = true;
    }

    function initializePublicationFilter() {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        let input = OO.ui.infuse($('#ce-publication-filter'));
        input.on('enter', () => {
            input.pushPending();
            let category = mw.config.get('wgCanonicalNamespace') === 'Category' ? mw.config.get('wgTitle') : 'Topic';
            ajax.getPublications(category, input.getValue()).done((result) => {
                input.popPending();
                let list = $('#ce-publication-list');
                list.empty();
                list.append(result.html);
            }).error(()=> {
                input.popPending();
            });
        });
    }

    function initializeMoleculesFilter() {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        let input = OO.ui.infuse($('#ce-molecules-filter'));
        input.on('enter', () => {
            input.pushPending();
            ajax.searchForMolecule(input.getValue()).done((result) => {
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
                let a = $('<a>')
                    .attr('href', mw.config.get('wgScriptPath') + '/Special:Search?search=' + encodeURIComponent(input.getValue()))
                    .attr('target', '_blank')
                    .append('open "'+input.getValue()+'" in fulltext search');
                container.append(a);
            }).error(()=> {
                input.popPending();
            });
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