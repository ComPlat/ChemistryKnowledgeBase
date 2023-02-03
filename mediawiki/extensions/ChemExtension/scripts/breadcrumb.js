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
        initializePublicationFilter();
        initialized = true;
    }

    function initializePublicationFilter() {
        let ajax = new window.ChemExtension.AjaxEndpoints();
        let filterInput = $('#ce-publication-filter input');
        filterInput.keydown((e) => {
            if (e.keyCode === 13) {
                ajax.getPublications(mw.config.get('wgTitle'), filterInput.val()).done((result) => {
                    let list = $('#ce-publication-list');
                    list.empty();
                    list.append(result.html);

                });
            }
        })
    }

    $(function() {
        if (!initialized) {
            initialize();
        }
    });

    mw.hook( 'postEdit' ).add(function() {
        if (!initialized) {
            initialize();
        }
    });

}(jQuery));