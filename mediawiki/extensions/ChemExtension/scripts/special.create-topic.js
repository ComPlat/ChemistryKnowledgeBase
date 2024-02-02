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

    $(function() {
        initialize();
    });


}(jQuery));