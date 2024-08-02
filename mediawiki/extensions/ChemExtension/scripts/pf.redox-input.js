(function ($) {
    'use strict';

    mw.hook('pf.addTemplateInstance').add(function() {
        $('button.ce_redoxinput_button').off('click');
        $('button.ce_redoxinput_button').click((e)=> {
            e.preventDefault();
            e.stopPropagation();
            alert("Hallo");
            console.log(e);
        });
    });

    mw.hook('pf.formSetupAfter').add(function() {
        $('button.ce_redoxinput_button').off('click');
        $('button.ce_redoxinput_button').click((e)=> {
            e.preventDefault();
            e.stopPropagation();
            alert("Hallo");
            console.log(e);
        });
    });


}(jQuery));