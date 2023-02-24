(function ($) {
    'use strict';

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.initPFFieldCounter = 1;
    window.ChemExtension.initPFExtensions = function() {
        $('span.pf_copy_template').off('click');
        $('span.pf_copy_template').click((e) => {
            let target = $(e.target);
            let newInstance = target.parent().clone(true);
            window.ChemExtension.initPFFieldCounter++;
            newInstance.find("input, select, textarea").each(function() {
                if (this.name) {
                    var old_name = this.name.replace(/\[\w+\]/, '['+window.ChemExtension.initPFFieldCounter+'z]');
                    $(this).attr('origName', old_name);
                    $(this).attr('name', old_name);

                }
            });
            target.parent().parent().append(newInstance);
        });
    }

    $(function() {
        window.ChemExtension.initPFExtensions();
    });

    mw.hook( 'pf.addTemplateInstance' ).add(function() {
        window.ChemExtension.initPFExtensions();
    });

}(jQuery));