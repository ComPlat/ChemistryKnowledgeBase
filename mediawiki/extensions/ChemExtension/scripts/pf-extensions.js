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

            // copy combo boxes
            let select2s = instanceToCopy.find('select[data-select2-id]');
            let select2sNew = newInstance.find('select[data-select2-id]');
            for(let i = 0; i < select2s.length; i++) {
                let combo = select2s.eq(i).select2();
                var option = new Option( combo.val(), combo.val(), false, true );
                select2sNew.eq(i).append(option).trigger('change');
            }

            // copy simple inputs
            let inputs = instanceToCopy.find('span.inputSpan input');
            let inputsNew = newInstance.find('span.inputSpan input');
            for(let i = 0; i < inputs.length; i++) {
                let input = inputs.eq(i);
                inputsNew.eq(i).val(input.val());
            }

            // copy standard drop-downs
            let selects = instanceToCopy.find('span.inputSpan select:not([data-select2-id])');
            let selectsNew = newInstance.find('span.inputSpan select:not([data-select2-id])');
            selectsNew.empty();
            for(let i = 0; i < selects.length; i++) {
                let options = selects.eq(i).find('option');
                selectsNew.eq(i).append(options.clone());
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