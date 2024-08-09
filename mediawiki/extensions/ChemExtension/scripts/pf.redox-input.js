(function ($) {
    'use strict';

    mw.hook('pf.addTemplateInstance').add(function () {
        $('button.ce_redoxinput_button').off('click');
        $('button.ce_redoxinput_button').click((e) => {
            e.preventDefault();
            e.stopPropagation();
            let formField = $(e.target).siblings('input');
            openDialog(formField);
        });
    });

    mw.hook('pf.formSetupAfter').add(function () {
        $('button.ce_redoxinput_button').off('click');
        $('button.ce_redoxinput_button').click((e) => {
            e.preventDefault();
            e.stopPropagation();
            let formField = $(e.target).siblings('input');
            openDialog(formField);
        });
    });

    function openDialog(formField) {
        let windowManager = OO.ui.getWindowManager();
        let dialog = new ve.ui.ChooseRedoxPotential({showText: 'Choose redox potential', 'formField' : formField});
        $(window.parent.document.body).append(windowManager.$element);
        windowManager.addWindows([dialog]);
        ve.ui.ChooseRedoxPotential.static.hideFormEditor();
        windowManager.openWindow(dialog);
    }


}(jQuery));

(function (OO) {
    'use strict';

    ve.ui.ChooseRedoxPotential = function VeUiChooseRedoxPotential(config) {
        // Parent constructor
        ve.ui.ChooseRedoxPotential.super.call(this, config);
        config = config || {};
        this.formField = config.formField;
        this.showText = config.showText || '-configure title-';
    };

    /* Inheritance */

    OO.inheritClass(ve.ui.ChooseRedoxPotential, OO.ui.MessageDialog);

    /* Static Properties */

    ve.ui.ChooseRedoxPotential.static.name = 'progress-chem';

    ve.ui.ChooseRedoxPotential.static.size = 'medium';

    ve.ui.ChooseRedoxPotential.static.actions = [];

    /* Methods */

    /**
     * @inheritdoc
     */
    ve.ui.ChooseRedoxPotential.prototype.initialize = function () {
        // Parent method
        ve.ui.ChooseRedoxPotential.super.prototype.initialize.call(this);


    };

    ve.ui.ChooseRedoxPotential.prototype.getBodyHeight = function () {
        return 250;
    }

    /**
     * @inheritdoc
     */
    ve.ui.ChooseRedoxPotential.prototype.getSetupProcess = function (data) {
        data = data || {};

        // Parent method
        return ve.ui.ChooseRedoxPotential.super.prototype.getSetupProcess.call(this, data)
            .next(() => {
                this.text.$element.empty();
                let $row = $('<div>').addClass('ve-ui-redoxDialog-row');
                this.oxidationPotential = new OO.ui.RedoxMultiSelectWidget();
                let flOxidationPotential = new OO.ui.FieldLayout(
                    this.oxidationPotential,
                    {
                        label: 'Oxidation potential (decimal number with optional asterisk)',
                        align: 'top'
                    }
                );
                this.reductionPotential = new OO.ui.RedoxMultiSelectWidget();
                let flReductionPotential = new OO.ui.FieldLayout(
                    this.reductionPotential,
                    {
                        label: 'Reduction potential (decimal number with optional asterisk)',
                        align: 'top'
                    }
                );
                let formData = this.formField.val().split(';');
                let oxValues = formData[0].split(",").filter(x => x.trim() !== '');
                let rdValues = formData.length > 1 ? formData[1].split(",").filter(x => x.trim() !== '') : [];
                this.oxidationPotential.setValue(oxValues);
                this.reductionPotential.setValue(rdValues);
                let closeButton = new OO.ui.ButtonWidget({
                    label: 'Close'
                }).on( 'click', () => {
                    this.close();
                    ve.ui.ChooseRedoxPotential.static.showFormEditor();
                });
                let applyButton = new OO.ui.ButtonWidget({
                    label: 'Apply'
                }).on( 'click', () => {
                    this.close();
                    const oxValue = this.oxidationPotential.getValue();
                    const rdValue = this.reductionPotential.getValue();
                    if (oxValue.length > 0 || rdValue.length > 0) {
                        this.formField.val(oxValue.join(', ') + " ; " + rdValue.join(', '));
                    } else {
                        this.formField.val('');
                    }
                    ve.ui.ChooseRedoxPotential.static.showFormEditor();
                });
                let buttonContainer = $('<div>').addClass('ve-ui-redoxDialog-row');
                buttonContainer.addClass('ce-redox-input-buttons');
                buttonContainer.append(applyButton.$element);
                buttonContainer.append(closeButton.$element);
                $row.append( flOxidationPotential.$element );
                $row.append( flReductionPotential.$element );
                $row.append( buttonContainer );

                this.text.$element.append($row);
                this.actions.setMode('default');

            }, this);
    };

    ve.ui.ChooseRedoxPotential.static.showFormEditor = function() {
        $('.popupform-wrapper', window.parent.document).css({'z-index': 10001});
    }

    ve.ui.ChooseRedoxPotential.static.hideFormEditor = function() {
        $('.popupform-wrapper', window.parent.document).css({'z-index': 2});
    }


    /* Static methods */

    /* Registration */

    ve.ui.windowFactory.register(ve.ui.ChooseRedoxPotential);


}(OO));