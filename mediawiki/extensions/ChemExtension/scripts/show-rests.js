(function($) {

    $(function () {
        $('span.ce-show-rests-button').click(function(e) {
            let span = $(e.target);
            $('div.ce-show-table', span.parent()).toggle();
            if (span.text() == '[Hide rests]') {
                span.text('[Show rests]')
            } else {
                span.text('[Hide rests]')
            }
        });
    });

})(jQuery);