(function ($) {
    'use strict';

    function initialize() {
        let topicInput = $('.chemtext-topic-input');
        if (topicInput.length === 0) return;
        let input = OO.ui.infuse(topicInput);
        input.namespace = 14;

        let tags = $('.chemtext-tags-input');
        if (tags.length === 0) return;
        OO.ui.infuse(tags);



    }

    function initializeCreateNewPaper() {
        let doSubmit = false;
        $('#chemext-create-paper').closest('form').on('submit', (e) => {
            if (doSubmit) return true;
            if ($('#chemext-doi input').val().trim() !== '') {
                return true;
            }
            OO.ui.confirm( 'It is not recommended to create a paper without a DOI. Do you want to proceed?' ).done( function ( confirmed ) {
                if (confirmed) {
                    let form = $(e.target).closest('form');
                    doSubmit = true;
                    form.submit();
                }
            } );
            return false;
        });
    }

    $(function() {
        initialize();
        initializeCreateNewPaper();
    });


}(jQuery));