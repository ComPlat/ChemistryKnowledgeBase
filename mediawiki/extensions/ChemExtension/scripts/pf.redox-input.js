(function ($) {
    'use strict';

    mw.hook('pf.addTemplateInstance').add(function () {
        registerListeners();
    });

    mw.hook('pf.formSetupAfter').add(function () {
        registerListeners();
    });

    function registerListeners() {
        $('button.ce_redoxinput_button').off('click');
        $('button.ce_redoxinput_button').click((e) => {
            e.preventDefault();
            e.stopPropagation();
            let formField = $(e.target).siblings('input.ce_redoxinput');
            openDialog(formField);
        });

        $('button.ce_redoxinput_file_button').click((e) => {
            e.preventDefault();
            e.stopPropagation();
            $(e.target).siblings('input.ce_redoxinput_file_input').click();
        });
        $('input.ce_redoxinput_file_input').change((e) => {
            readRedoxFile(e);
        });
    }

    function openDialog(formField) {
        let windowManager = OO.ui.getWindowManager();
        let dialog = new ve.ui.ChooseRedoxPotential({showText: 'Choose redox potential', 'formField' : formField});
        $(window.parent.document.body).append(windowManager.$element);
        windowManager.addWindows([dialog]);
        ve.ui.ChooseRedoxPotential.static.hideFormEditor();
        windowManager.openWindow(dialog);
    }

    function readRedoxFile(e) {
        let target = $(e.target);
        const file = target.get(0).files[0], read = new FileReader();
        let formField = target.siblings('input.ce_redoxinput');
        read.readAsArrayBuffer(file);
        read.onloadend = () => {
            let enc = new TextDecoder("utf-8");
            let content = enc.decode(read.result);
            try {
                formField.val(new RedoxCSVParser().parseCSV(content));
                openDialog(formField);
            } catch(e) {
                mw.notify(e, {type: 'error'});
            }
        }
    }

    function RedoxCSVParser() {
        let that = {};

        that.parseCSV = function(content) {
            let lines = content.split(/\n|\r\n/);
            let result = [];
            if (lines.length > 10) {
                for(let i = 9; i < lines.length; i++) {
                    let columns = lines[i].split(/,/);
                    if (columns.length > 6) result.push(Math.round(columns[6] * 100) / 100);
                }
            }
            if (result.length === 0) {
                throw 'Could not import file. File did not match expected CSV format';
            }
            return result.join(", ") + "; " + result.join(", ");
        }

        return that;
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
        return 320;
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
                let content = $('<div>').addClass('ve-ui-redoxDialog-content');
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
                let rdValues = formData[0].split(",").filter(x => x.trim() !== '');
                let oxValues = formData.length > 1 ? formData[1].split(",").filter(x => x.trim() !== '') : [];
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
                        this.formField.val(rdValue.join(', ') + " ; " + oxValue.join(', '));
                    } else {
                        this.formField.val('');
                    }
                    ve.ui.ChooseRedoxPotential.static.showFormEditor();
                });
                let buttonContainer = $('<div>').addClass('ce-redox-input-buttons');
                buttonContainer.append(applyButton.$element);
                buttonContainer.append(closeButton.$element);

                let fieldsContainer = $('<div>').addClass('ve-ui-redoxDialog-fieldsContainer');
                fieldsContainer.append( flReductionPotential.$element );
                fieldsContainer.append( flOxidationPotential.$element );
                content.append( fieldsContainer );
                content.append( buttonContainer );

                this.text.$element.append(content);
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