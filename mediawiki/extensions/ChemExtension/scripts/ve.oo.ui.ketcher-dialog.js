/* Translate the following to your language: */
mw.loader.using('ext.visualEditor.core').then(function () {

    if (!mw.messages.exists( 've-KetcherDialog-title' )) {
        mw.messages.set({
            've-KetcherDialog-title': 'Ketcher',

        });
    }
    /* end of translations */

    ve.ui.KetcherDialog = function( manager, config ) {
        // Parent constructor
        ve.ui.KetcherDialog.super.call( this, manager, config );

    };
    /* Inheritance */

    OO.inheritClass( ve.ui.KetcherDialog, ve.ui.FragmentDialog );


    ve.ui.KetcherDialog.prototype.getActionProcess  = function ( action ) {

        return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call( this, action );
    }
    ve.ui.KetcherDialog.prototype.setup = function(data) {

        this.iframe.setFormula(data.formula);
        return ve.ui.KetcherDialog.super.prototype.setup.call(this, data);
    };

    ve.ui.KetcherDialog.prototype.getBodyHeight = function () {
        return 600;
    };

    /* Static Properties */
    ve.ui.KetcherDialog.static.name = 'edit-with-ketcher';
    ve.ui.KetcherDialog.static.title = mw.msg( 've-KetcherDialog-title' );
    ve.ui.KetcherDialog.static.size = 'medium';

    ve.ui.KetcherDialog.static.actions = [

        {
            'label': OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
            'flags': 'safe',
            'modes': [ 'edit', 'insert', 'select' ]
        }
    ];

    ve.ui.KetcherDialog.prototype.initialize = function () {
        ve.ui.KetcherDialog.super.prototype.initialize.call( this );
        this.setSize("larger");
        this.panel = new OO.ui.PanelLayout( { '$': this.$, 'scrollable': true, 'padded': true } );

        this.iframe = new OO.ui.KetcherWidget();
        this.panel.$element.append(	this.iframe.$element );


        this.$body.append( this.panel.$element );

    }

    ve.ui.windowFactory.register( ve.ui.KetcherDialog );


});