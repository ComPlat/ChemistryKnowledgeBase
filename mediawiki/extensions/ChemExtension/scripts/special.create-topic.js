(function ($) {
    'use strict';

    function initialize() {
        let topicInput = $('.chemtext-topic-input');
        if (topicInput.length === 0) return;
        let input = OO.ui.infuse(topicInput);
        input.namespace = 14;
    }

    $(function() {
        initialize();
    });


}(jQuery));