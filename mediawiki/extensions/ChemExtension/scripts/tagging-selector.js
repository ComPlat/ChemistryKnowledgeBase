(function ($) {
    'use strict';

    let dialog = null;
    let handle = null;
    window.addEventListener("dblclick", (event) => {
        if (handle) {
            clearTimeout(handle);
        }

    });

    window.addEventListener("mouseup", (event) => {
        if (!event.ctrlKey) {
            return;
        }
        handle = setTimeout(() => {
            var windowManager = new OO.ui.WindowManager();
            let t = $(event.target);
            if (t.closest('div.oo-ui-messageDialog-text').length > 0) {
                return;
            }
            if (dialog) {
                dialog.close();
                dialog.updateTagsNode();
                dialog = null;
                document.getSelection().collapse(document.documentElement);
                return;
            }

            let selectedText = document.getSelection().toString();
            if (selectedText == '' || document.getSelection().isCollapsed) {
                return;
            }

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
                        label: 'Enter term',
                        align: 'top'
                    }
                );
                let tagsNode = this.findTagsNode();
                if (tagsNode != null) {
                    let template = ve.ui.LinearContextItemExtension.getTemplate(tagsNode.model);
                    let items = template.params.tags.wt.split(",")
                    this.inputField.setValue(items);
                }
                setTimeout(() => {
                    this.inputField.focus();
                    this.inputField.changing = true;
                    this.inputField.input.setValue(this.selectedText.trim());
                    setTimeout(() => {
                        this.inputField.changing = false;
                    }, 200)

                }, 300);

                $row.append( fieldLayout.$element );
                this.text.$element.append( $row );
                this.actions.setMode( 'default' );

            }, this );
    };


    /* Static methods */

    /* Registration */

    ve.ui.windowFactory.register( ve.ui.TaggingDialog );

    ve.ui.TaggingDialog.prototype.updateTagsNode = function () {
        let tagsNode = this.findTagsNode();
        if (tagsNode != null) {
            let template = ve.ui.LinearContextItemExtension.getTemplate(tagsNode.model);
            let value = this.inputField.getValue();
            template.params.tags.wt = value.join(',');
            tagsNode.forceUpdate();
        }

    }

    ve.ui.TaggingDialog.prototype.findTagsNode = function () {
        let tagsNode = null;
        let documentNode = ve.init.target.getSurface().getView().getDocument().getDocumentNode();
        let iterate = (node) => {
            if (!node.children) return;
            for (let i = 0; i < node.children.length; i++) {
                let child = node.children[i];
                if (child.type === 'mwTransclusionBlock') {
                    let template = ve.ui.LinearContextItemExtension.getTemplate(child.model);
                    let target = template.target.wt;
                    if (target === 'Tags') {
                        tagsNode = child;
                    }
                }
                iterate(child);
            }
        }
        iterate(documentNode);
        return tagsNode;
    }


}(OO));