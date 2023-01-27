(function ($) {
    'use strict';

    let initialized = false;
    function initialize() {
        $('#ce-side-panel-content').mouseleave((e) => {
            $('#ce-side-panel-content').hide();
        })
        $('.ce-side-panel-bar').click(() => {
            $('#ce-side-panel-content').toggle();
        });
        initialized = true;
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