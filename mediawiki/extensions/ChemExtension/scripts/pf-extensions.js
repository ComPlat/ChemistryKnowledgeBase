(function ($) {
    'use strict';

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.initPFFieldCounter = 1;
    window.ChemExtension.initPFExtensions = function() {
        $('span.pf_copy_template').off('click');
        $('span.pf_copy_template').click((e) => {
            let target = $(e.target);
            let instanceToCopy = target.parent();
            let newInstance = $('.multipleTemplateAdder').addInstance(false);

            // copy all inputs except checkboxes
            let inputs = instanceToCopy.find('input:not([type="checkbox"])');
            let inputsNew = newInstance.find('input:not([type="checkbox"])');
            for(let i = 0; i < inputs.length; i++) {
                let value = inputs.eq(i).val();
                inputsNew.eq(i).val(value);
            }

            let checkboxes = instanceToCopy.find('input[type="checkbox"]');
            let checkboxesNew = newInstance.find('input[type="checkbox"]');
            for(let i = 0; i < checkboxes.length; i++) {
                let value = checkboxes.eq(i).is(':checked');
                checkboxesNew.eq(i).prop('checked', value);
            }

            //TODO: copy others like date picker

            setTimeout(() => {
                $('.multipleTemplateInstance').last().trigger('click');
                removeValuesAfterClick(newInstance);

            }, 300);
        });
    }

    let removeValuesAfterClick = function(newInstance) {
        let el = newInstance;
        let f = function() {
            if (el.find('.fieldValuesDisplay').length > 0) {
                el.find('.fieldValuesDisplay').remove();
            } else {
                setTimeout(f, 100);
            }
        }
        f();
    }

    $(function() {
        window.ChemExtension.initPFExtensions();
    });

    mw.hook( 'pf.addTemplateInstance' ).add(function() {
        window.ChemExtension.initPFExtensions();
    });

}(jQuery));