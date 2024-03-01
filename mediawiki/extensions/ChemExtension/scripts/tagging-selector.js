(function ($) {
    'use strict';

    let dialog = null;
    let handle = null;
    let node = null;
    let range = null;
    let lastText = null;
    window.addEventListener("dblclick", (event) => {
        if (handle) {
            clearTimeout(handle);
        }

    });

    window.addEventListener("mouseup", (event) => {
        if (!event.ctrlKey && dialog === null) {
            return;
        }


        handle = setTimeout(() => {
            var windowManager = new OO.ui.WindowManager();
            let t = $(event.target);
            if (t.closest('div.oo-ui-messageDialog-container').length > 0) {
                return;
            }
            if (dialog) {
                let tags = dialog.inputField.getValue();
                dialog.close();

                let surface = ve.init.target.getSurface();
                ve.ce.AnnotateMouseDownHandler.static.execute(surface, event, node, range, tags, lastText);

                dialog = null;
                document.getSelection().collapse(document.documentElement);
                return;
            }

            let selectedText = document.getSelection().toString();
            if (selectedText == '' || document.getSelection().isCollapsed) {
                return;
            }
            let surface = ve.init.target.getSurface();
            range = surface.getView().model.getSelection().getRange();
            node = surface.getView().getDocument().getBranchNodeFromOffset( range.from );
            lastText = selectedText;

            dialog = new ve.ui.TaggingDialog({selectedText: selectedText});
            $( document.body ).append( windowManager.$element );
            windowManager.addWindows( [ dialog ] );
            windowManager.openWindow( dialog);
            handle = null;
        }, 300);


    });


}(jQuery));


(function (OO) {
    'use strict';

    ve.ui.TaggingDialog = function VeUiTaggingDialog( config ) {
        // Parent constructor
        ve.ui.TaggingDialog.super.call( this, config );
        this.selectedText = config.selectedText || '';
        this.data = config.data || [];
    };

    /* Inheritance */

    OO.inheritClass( ve.ui.TaggingDialog, OO.ui.MessageDialog );

    /* Static Properties */

    ve.ui.TaggingDialog.static.name = 'annotation';

    ve.ui.TaggingDialog.static.size = 'medium';

    ve.ui.TaggingDialog.static.actions = [];

    /* Methods */

    /**
     * @inheritdoc
     */
    ve.ui.TaggingDialog.prototype.initialize = function () {
        // Parent method
        ve.ui.TaggingDialog.super.prototype.initialize.call( this );


    };

    ve.ui.TaggingDialog.prototype.getBodyHeight = function () {
        return 400;
    };

    /**
     * @inheritdoc
     */
    ve.ui.TaggingDialog.prototype.getSetupProcess = function ( data ) {
        data = data || {};

        // Parent method
        return ve.ui.TaggingDialog.super.prototype.getSetupProcess.call( this, data )
            .next( () => {
                this.text.$element.empty();
                let $row = $( '<div>' ).addClass( 've-ui-annotationDialog-row' );
                this.inputField = new OO.ui.TagsMultiSelectWidget();
                this.inputField.$element.addClass('chemtext-tags-input')
                let fieldLayout = new OO.ui.FieldLayout(
                    this.inputField,
                    {
                        label: 'Enter annotations',
                        align: 'top'
                    }
                );
                let description = new OO.ui.LabelWidget({label: 'Please select annotations from the list. You can ' +
                        'enter arbitrary terms as well as suggested terms. The annotations will later help users to find' +
                        ' documents in the search. They can also be used to query for documents'});
                let fieldLayout2 = new OO.ui.FieldLayout(
                    description,
                    {
                        label: '',
                        align: 'top'
                    }
                );
                let url = mw.config.get('wgScriptPath') + '/Help:Tagging';
                let helpLink = new OO.ui.HtmlSnippet('<a target="_blank" href="'+url+'">Help</a>')
                this.inputField.setValue(this.data);
                setTimeout(() => {
                    this.inputField.focus();
                    this.inputField.changing = true;
                    this.inputField.input.setValue(this.selectedText.trim());
                    setTimeout(() => {
                        this.inputField.changing = false;
                    }, 200)

                }, 300);

                $row.append( fieldLayout.$element );
                $row.append( fieldLayout2.$element );
                $row.append( helpLink.toString() );
                this.text.$element.append( $row );
                this.actions.setMode( 'default' );

            }, this );
    };


    /* Static methods */

    /* Registration */

    ve.ui.windowFactory.register( ve.ui.TaggingDialog );


}(OO));