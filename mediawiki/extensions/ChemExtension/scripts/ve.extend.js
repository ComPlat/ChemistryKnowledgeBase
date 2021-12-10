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
            var basePath = mw.config.get("wgServer") + mw.config.get("wgScriptPath");
            var chemForm = this.originalMwData.body.extsrc;
            var button = new OO.ui.ButtonWidget( {
                label: 'Open Ketcher',
                href: basePath + "/index.php/Spezial:KetcherEditor?chemform="+encodeURIComponent(chemForm),
                target: '_blank'
            } );
            this.$attributes.append( button.$element );

            this.title.setLabel( this.selectedNode.getExtensionName() );
        }, this);

    };


} );