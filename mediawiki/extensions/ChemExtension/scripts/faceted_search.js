(function ($) {
    'use strict';

    window.XFS = window.XFS || {};
    let xfs = window.XFS || {};

    xfs.addAdditionalActions = function(output, doc) {
        let html = "";
        let links = [];
        let baseUrl = mw.config.get( 'wgScript' ) + '/';
        if (doc.smwh_AuthorPage_t) {
            html += '<div><span>Has authors: </span>'
            $.each(doc.smwh_AuthorPage_t, (i, e) => {
                let parts = e.split("|");
                let pagename = parts[0].replace(/\s/,'_');
                let label = parts[1] ? parts[1] : parts[0];
                links.push('<span class="xfs_action"><a href="' + baseUrl + pagename + '">'+label+'</a></span>');

            });
        }

        html += links.join(', ');
        html += '</<div>'

        return html;
    };



}(jQuery));