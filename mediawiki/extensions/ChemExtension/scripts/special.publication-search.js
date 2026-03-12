(function ($) {
    'use strict';

    function initialize() {
        $('.approved-checkbox').change(function(e) {
            let doi = $(e.target).attr('name');
            let isApproved = $(e.target).is(':checked');
            let url = mw.config.get('wgScriptPath') + '/rest.php/ChemExtension/v1/publication-approve';
            $.ajax({
                url: url,
                type: 'POST',
                contentType: "application/json",
                data: JSON.stringify({
                    doi: doi,
                    isApproved: isApproved
                })
            })
        });

        $('.download-button').click(function(e) {
            let doi = $(e.target).attr('doi');
            let url = mw.config.get('wgScriptPath') + '/rest.php/ChemExtension/v1/download-publication-pdf?doi=' + encodeURIComponent(doi);
            $.ajax({
                url: url,
                type: 'GET',
                success: (blob, status, xhr) => {
                    window.open(blob, '_blank').focus();
                }
            });
        })
    }

    $(function() {
        initialize();
    });


}(jQuery));