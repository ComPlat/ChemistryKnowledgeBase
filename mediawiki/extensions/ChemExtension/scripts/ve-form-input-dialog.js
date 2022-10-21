/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists('ve-ChooseExperimentDialog-title')) {
        mw.messages.set({
            've-ChooseExperimentDialog-title': 'Choose investigation type',

        });
    }

    ve.ui.ChooseExperimentDialog = function (manager, config) {
        // Parent constructor
        ve.ui.ChooseExperimentDialog.super.call(this, manager, config);

    };
    /* Inheritance */

    OO.inheritClass(ve.ui.ChooseExperimentDialog, ve.ui.FragmentDialog);


    ve.ui.ChooseExperimentDialog.prototype.getActionProcess = function (action) {
        if (action === 'apply') {
            return new OO.ui.Process(() => {
                let selectedExperiment = this.chooseExperimentsWidget.getSelectedExperiment();

                let toInsert = [ [
                    {
                        type: 'mwTransclusionInline',
                        attributes: {
                            mw: {
                                parts:  [
                                    {
                                        template: {
                                            i: 0,
                                            params: {
                                                form: { wt: selectedExperiment }
                                            },
                                            target: { wt: "#experimentlist:", "function": "experimentlist"}
                                        }
                                    }
                                ]
                            },
                            originalMw: '"{"parts":[{"template":{"target":{"wt":"#experimentlist:","function":"experimentlist"},"params":{"form":{"wt":""}},"i":0}}]}"'
                        }
                    }

                ]];
                this.surface.execute.apply( this.surface, [ 'content', 'insert' ].concat( toInsert ) );
                ve.ui.MWMediaDialog.super.prototype.close.call(this);
            }, this);
        }
        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call(this, action);
    }

    ve.ui.ChooseExperimentDialog.prototype.attachActions = function() {
        ve.ui.ChooseExperimentDialog.super.prototype.attachActions.call(this);
        //this.getActions().list[0].setDisabled(true);
    }

    ve.ui.ChooseExperimentDialog.prototype.setup = function (data) {
        this.chooseExperimentsWidget.setData(data);
        this.surface = data.surface;
        let setup = ve.ui.ChooseExperimentDialog.super.prototype.setup.call(this, data);
        return setup;
    };

    ve.ui.ChooseExperimentDialog.prototype.getBodyHeight = function () {
        return 200;
    };

    /* Static Properties */
    ve.ui.ChooseExperimentDialog.static.name = 'choose-experiments';
    ve.ui.ChooseExperimentDialog.static.title = mw.msg('ve-ChooseExperimentDialog-title');
    ve.ui.ChooseExperimentDialog.static.size = 'medium';

    ve.ui.ChooseExperimentDialog.static.actions = [
        {
            'action': 'apply',
            'label': mw.msg('visualeditor-dialog-action-apply'),
            'flags': ['safe'],
            'modes': ['edit', 'insert', 'select']
        },
        {
            'label': OO.ui.deferMsg('visualeditor-dialog-action-cancel'),
            'flags': 'safe',
            'modes': ['edit', 'insert', 'select']
        }
    ];

    ve.ui.ChooseExperimentDialog.prototype.initialize = function () {
        ve.ui.ChooseExperimentDialog.super.prototype.initialize.call(this);

        this.panel = new OO.ui.PanelLayout({'$': this.$, 'scrollable': true, 'padded': true});
        this.chooseExperimentsWidget = new OO.ui.ChooseExperimentsWidget(this);
        this.panel.$element.append(this.chooseExperimentsWidget.$element);

        this.$body.append(this.panel.$element);

    }

    ve.ui.windowFactory.register(ve.ui.ChooseExperimentDialog);


});