(function (OO) {
    'use strict';

    ve.ui.IndefiniteProgressDialog = function VeUiIndefiniteProgressDialog( config ) {
        // Parent constructor
        ve.ui.IndefiniteProgressDialog.super.call( this, config );
        config = config || {};
        this.showText = config.showText || 'In progress...';
    };

    /* Inheritance */

    OO.inheritClass( ve.ui.IndefiniteProgressDialog, OO.ui.MessageDialog );

    /* Static Properties */

    ve.ui.IndefiniteProgressDialog.static.name = 'progress-chem';

    ve.ui.IndefiniteProgressDialog.static.size = 'medium';

    ve.ui.IndefiniteProgressDialog.static.actions = [];

    /* Methods */

    /**
     * @inheritdoc
     */
    ve.ui.IndefiniteProgressDialog.prototype.initialize = function () {
        // Parent method
        ve.ui.IndefiniteProgressDialog.super.prototype.initialize.call( this );


    };

    /**
     * @inheritdoc
     */
    ve.ui.IndefiniteProgressDialog.prototype.getSetupProcess = function ( data ) {
        data = data || {};

        // Parent method
        return ve.ui.IndefiniteProgressDialog.super.prototype.getSetupProcess.call( this, data )
            .next( () => {
                this.text.$element.empty();
                let $row = $( '<div>' ).addClass( 've-ui-progressDialog-row' );
                let progressBar = new OO.ui.ProgressBarWidget({progress: false});
                let fieldLayout = new OO.ui.FieldLayout(
                    progressBar,
                    {
                        label: this.showText,
                        align: 'top'
                    }
                );
                $row.append( fieldLayout.$element );
                this.text.$element.append( $row );
                this.actions.setMode( 'default' );

            }, this );
    };


    /* Static methods */

    /* Registration */

    ve.ui.windowFactory.register( ve.ui.IndefiniteProgressDialog );


}(OO));