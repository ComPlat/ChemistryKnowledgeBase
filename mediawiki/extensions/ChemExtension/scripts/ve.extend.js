mw.libs.ve.addPlugin( function ( target ) {

  /*!
   * Extends the tag editor with a new button to open Ketcher
   */

    /**
     * @inheritdoc
     */
    var superClassMethod = ve.ui.MWAlienExtensionInspector.prototype.getSetupProcess;
    ve.ui.MWAlienExtensionInspector.prototype.getSetupProcess = function ( data ) {
        return superClassMethod.call( this, data ).next(function() {

            if (this.originalMwData.name != 'chemform') {
                return;
            }

            var chemForm = this.originalMwData.body.extsrc;
            var id = this.originalMwData.attrs.id;
            var button = new OO.ui.ButtonWidget( {
                label: 'Open Ketcher'
            } );

            button.on( 'click', function() {
                ve.init.target.getSurface().execute( 'window', 'open', 'edit-with-ketcher', { formula: chemForm, id: id } );
            } );

            this.$attributes.append( button.$element );

            this.title.setLabel( this.selectedNode.getExtensionName() );
        }, this);

    };


} );