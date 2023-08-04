(function ($) {
    'use strict';

    function initialize() {
        let pageTitleInput = $('.chemtext-page-title-input');
        if (pageTitleInput.length === 0) return;
        let input = OO.ui.infuse(pageTitleInput);
        input.namespace = 0;
    }

    $(function() {
        initialize();
    });


}(jQuery));