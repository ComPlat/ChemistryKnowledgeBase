( function () {
    'use strict';

    /**
     * Custom OOUI ProcessDialog with a panel, label and button.
     *
     * @class EditMoleculeInInvestigation
     * @extends OO.ui.ProcessDialog
     *
     * @param {Object} [config] Configuration options
     */
    function EditMoleculeInInvestigation(config ) {
        EditMoleculeInInvestigation.super.call( this, config );
    }
    OO.inheritClass( EditMoleculeInInvestigation, OO.ui.ProcessDialog );

    // Static properties
    EditMoleculeInInvestigation.static.name = 'edit_molecule_in_investigation';
    EditMoleculeInInvestigation.static.title = 'Edit molecule in investigation';
    EditMoleculeInInvestigation.static.actions = [
        { action: 'close', label: 'Close', flags: [ 'safe', 'close' ] }
    ];

    /**
     * Build the dialog UI: a PanelLayout containing a LabelWidget and a ButtonWidget.
     *
     * @inheritdoc
     */
    EditMoleculeInInvestigation.prototype.initialize = function () {
        EditMoleculeInInvestigation.super.prototype.initialize.call( this );

        this.panel = new OO.ui.PanelLayout( {
            padded: true,
            expanded: false
        } );

        this.button = new OO.ui.ButtonWidget( {
            label: 'Update molecule in investigation',
            flags: [ 'primary', 'progressive' ]
        } );

        // Handle the button click
        this.button.on( 'click', () => {
            let ajax = new window.ChemExtension.AjaxEndpoints();
            let inchiKey = this.textWidget.getValue();
            let request = {
                inchiKey: inchiKey,
                moleculeAsText: this.requestData.moleculeText,
                investigationPage: this.requestData.investigationPage,
            }
            var self = this;
            self.setLoading( true );
            ajax.updateMoleculeInInvestigation(request).done((response) => {
                OO.ui.alert( 'Updated molecule in investigation.' );
            }).catch((e) => {
                OO.ui.alert( 'Could not update molecule in investigation.' );
            }).always( () => {
                self.setLoading( false );
                this.requestData.refreshButton.trigger('click');
                this.close( { action: 'close' } );
            } );
        } );

        this.textWidget = new OO.ui.InchiKeyLookupTextInputWidget();
        let label = new OO.ui.LabelWidget({label: "Molecule-ID (Trivial name, CAS, Abbreviation or Synonyms)"});
        let formLayout = new OO.ui.FormLayout({
            items: [label,this.textWidget]
        });
        this.panel.$element.append(formLayout.$element);
        this.panel.$element.append( $( '<br>' ), this.button.$element );

        // Loading indicator (indeterminate progress bar)
        this.progressBar = new OO.ui.ProgressBarWidget( {
            progress: false // false = indeterminate
        } );
        this.progressBar.$element
            .addClass( 'ce-updateMolecule-progress' )
            .hide(); // hidden by default

        // Optional label shown next to / above the progress bar
        this.progressLabel = new OO.ui.LabelWidget( {
            label: 'Updating molecule, please wait…'
        } );
        this.progressLabel.$element.hide();

        this.panel.$element.append(
            this.progressLabel.$element,
            this.progressBar.$element
        );

        this.$body.append( this.panel.$element );
    };

    /**
     * Toggle the loading indicator inside the dialog.
     * Also disables/enables the dialog's action buttons so the user
     * can't submit twice while the AJAX call is running.
     *
     * @param {boolean} loading
     */
    EditMoleculeInInvestigation.prototype.setLoading = function ( loading ) {
        this.progressBar.$element.toggle( loading );
        this.progressLabel.$element.toggle( loading );

        // Disable all actions (Save/Cancel/etc.) while loading
        this.getActions().get().forEach( function ( action ) {
            action.setDisabled( loading );
        } );

        // Also update the OOUI "pending" state on the dialog window itself,
        // which shows a subtle progress stripe in the title bar.
        if ( loading ) {
            this.pushPending();
        } else {
            this.popPending();
        }
    };

    /**
     * Handle dialog actions (Close).
     *
     * @inheritdoc
     */
    EditMoleculeInInvestigation.prototype.getActionProcess = function (action ) {
        if ( action === 'close' ) {
            return new OO.ui.Process( () => {
                this.close( { action: action } );
            } );
        }
        return EditMoleculeInInvestigation.super.prototype.getActionProcess.call( this, action );
    };

    /**
     * Provide a reasonable initial body height.
     *
     * @inheritdoc
     */
    EditMoleculeInInvestigation.prototype.getBodyHeight = function () {
        return 400;
    };

    EditMoleculeInInvestigation.prototype.setInvestigationData = function (requestData) {
        this.requestData = requestData;
    };

    // ---- Wire it up ----

    $( () => {
        // Create a WindowManager once and reuse it.
        const windowManager = new OO.ui.WindowManager();
        $( document.body ).append( windowManager.$element );

        const dialog = new EditMoleculeInInvestigation( { size: 'medium' } );
        windowManager.addWindows( [ dialog ] );

        $('div.experiment-list-container').each(function(i, e) {
            let el = $(e);
            let exportButton  = $('span.experiment-link-export-button button', el);
            let refreshButton  = $('span.experiment-list-refresh-button button', el);
            let investigationData = JSON.parse(exportButton.attr('value'));
            let url = mw.config.get('wgScriptPath')+"/index.php?";
            url += "title="+encodeURIComponent(mw.config.get('wgTitle'));
            url += "&veaction=edit";
            $('a.new').qtip({
                content: "<div class='chemform-edit-in-investigation'>"
                    +"<a class='chemform-use-existing'>Use existing molecule...</a>"
                    +"<br/>"
                    +"<a target='_blank' href='"+url+"'>Create new molecule...</a>"
                    +"</div>",
                style: { classes: 'replace-molecule-tooltip' },
                events: {

                    render: function(event, api) {
                        let moleculeText = $(api.elements.target).text();
                        let tooltip = api.elements.tooltip;
                        let data = {
                            investigationPage: investigationData.investigationPage,
                            moleculeText: moleculeText,
                            refreshButton: refreshButton
                        }
                        $('a.chemform-use-existing',tooltip).off('click');
                        $('a.chemform-use-existing', tooltip).click((e) => {
                            e.preventDefault();
                            dialog.setInvestigationData(data);
                            windowManager.openWindow( dialog );
                        });

                    }
                },
                hide: {
                    fixed: true,
                    delay: 300
                },
                position: {
                    viewport: $(window)
                }
            });
        })

    } );

}() );