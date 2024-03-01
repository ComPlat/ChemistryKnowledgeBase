mw.loader.using('ext.visualEditor.core').then(function () {

    ve.ui.MWAlienExtensionInspectorExtension = {};

    ve.ui.MWAlienExtensionInspectorExtension.extend = function(panel) {
        if (panel.originalMwData.name != 'chemform') {
            return;
        }
        let tools = new OO.VisualEditorTools();

        var molfile = panel.originalMwData.body.extsrc;
        var smiles = panel.originalMwData.attrs.smiles;

        var button = new OO.ui.ButtonWidget({
            label: 'Open Ketcher'
        });

        button.on('click', function () {
            ve.init.target.getSurface().execute('window', 'open', 'edit-with-ketcher', {
                formula: panel.input.value,
                smiles: panel.attributeInputs.smiles.value,
                inchikey: panel.attributeInputs.inchikey.value,
                node: panel.selectedNode
            });
        });

        panel.$attributes.append(button.$element);

        button = new OO.ui.ButtonWidget({
            label: 'Define R-Groups',
            disabled: tools.getNumberOfMoleculeRGroups(molfile) === 0
        });

        button.on('click', function () {
            ve.init.target.getSurface().execute('window', 'open', 'edit-molecule-rgroups', {
                attrs: panel.originalMwData.attrs,
                numberOfMoleculeRGroups: tools.getNumberOfMoleculeRGroups(molfile),
                rGroupIds: tools.getRGroupIds(molfile),
                node: panel.selectedNode
            });
        });

        panel.$attributes.append(button.$element);

        panel.title.setLabel(panel.selectedNode.getExtensionName());
    }

    ve.ui.LinearContextItemExtension = {};
    ve.ui.LinearContextItemExtension.extend = function(panel, context, model) {

        let template = ve.ui.LinearContextItemExtension.getTemplate(model);
        if (template == null || template.target == null) {
            return;
        }

        if (template.target.wt.trim() == '#literature:') {
            ve.ui.LinearContextItemExtension.extendForLiterature(panel, context, model);
        } else if (template.target.wt.trim() == '#experimentlist:') {
            ve.ui.LinearContextItemExtension.extendForExperimentList(panel, context, model);
        } else if (template.target.wt.trim() == '#moleculelink:') {
            ve.ui.LinearContextItemExtension.extendForMoleculeLink(panel, context, model);
        } else if (template.target.wt.trim().indexOf('#experimentlink:') === 0) {
            ve.ui.LinearContextItemExtension.extendForExperimentLink(panel, context, model);
        } else if (template.target.wt.trim() == 'Annotation') {
            ve.ui.LinearContextItemExtension.extendForTagTemplate(panel, context, model);
        }
    }

    ve.ui.LinearContextItemExtension.extendForExperimentList = function(panel, context, model) {
        let addButton = new OO.ui.ButtonWidget({
            label: 'Add experiment'
        });

        let refreshButton = new OO.ui.ButtonWidget({
            label: 'Refresh'
        });

        refreshButton.on('click', function () {
            let tools = new OO.VisualEditorTools();
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            if (!template || !template.params || !template.params.form) return;
            let experimentName = template.params.name.wt;
            tools.refreshVENode((node) => {
                if (node.type === 'mwTransclusionBlock' || node.type === 'mwTransclusionInline') {
                    let params = node.model.element.attributes.mw.parts[0].template.params;
                    return (params.name && params.name.wt == experimentName);
                }
            });
        });

        addButton.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            if (!template || !template.params || !template.params.form) return;
            let form = template.params.form.wt;
            let name = template.params.name.wt;
            let wgScriptPath = mw.config.get('wgScriptPath');
            let wgPageName = mw.config.get('wgPageName');
            ext.popupform.handlePopupFormLink( wgScriptPath + '/Special:FormEdit/'+form + '/' + wgPageName + '/' + name, addButton.$element );
            ve.init.target.fromEditedState = true;
            ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();
        });

        panel.$actions.append(addButton.$element);
        panel.$actions.append(refreshButton.$element);
        panel.editButton.$element.remove();
    }

    ve.ui.LinearContextItemExtension.extendForLiterature = function(panel, context, model) {
        let button = new OO.ui.ButtonWidget({
            label: 'Open DOI'
        });

        button.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            if (!template || !template.params || !template.params.doi) return;
            let doi = template.params.doi.wt;
            var url;
            try {
                url = new URL(doi).toString();
            } catch(e) {
                // assume it's just a DOI, not a full URL
                url = 'https://dx.doi.org/' + doi;
            }
            window.open(url,'_blank');
        });

        panel.$actions.append(button.$element);
    }

    ve.ui.LinearContextItemExtension.extendForMoleculeLink = function(panel, context, model) {
        let editButton = new OO.ui.ButtonWidget({
            label: 'Edit link'
        });
        editButton.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            ve.init.target.getSurface().execute('window', 'open', 'choose-molecule', {
                //attrs: panel.originalMwData.attrs,
                template: template,
                node: model
            });
        });
        panel.$actions.append(editButton.$element);
    }

    ve.ui.LinearContextItemExtension.extendForExperimentLink = function(panel, context, model) {
        let addButton = new OO.ui.ButtonWidget({
            label: 'Edit link'
        });

        addButton.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            ve.init.target.getSurface().execute('window', 'open', 'choose-experiment-link', {
                template: template,
                node: model
            });

        });

        panel.$actions.append(addButton.$element);
        panel.editButton.$element.remove();
    }

    ve.ui.LinearContextItemExtension.extendForTagTemplate = function(panel, context, model) {
        let addButton = new OO.ui.ButtonWidget({
            label: 'Change annotations'
        });
        let dialog;
        addButton.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            let value = template.params.value.wt ? template.params.value.wt : '';
            dialog = new ve.ui.TaggingDialog({data: value.split(',')});
            let windowManager = new OO.ui.WindowManager();
            $( document.body ).append( windowManager.$element );
            windowManager.addWindows( [ dialog ] );
            windowManager.openWindow( dialog);

        });

        panel.$actions.append(addButton.$element);
        panel.editButton.$element.remove();

        window.addEventListener("mouseup", (event) => {
            let t = $(event.target);
            if (t.closest('div.oo-ui-messageDialog-container').length > 0) {
                return;
            }
            if (dialog) {
                dialog.close();
                let selectedNode = ve.init.target.getSurface().getModel().getSelectedNode();
                let template = ve.ui.LinearContextItemExtension.getTemplate(selectedNode);
                template.params.value.wt = dialog.inputField.getValue().join(',');
                ve.init.target.getSurface().getView().getSelection().focusedNode.forceUpdate();
                ve.init.target.fromEditedState = true;
                ve.init.target.getActions().getToolGroupByName('save').items[0].onUpdateState();
                dialog = null;
            }
        });
    }

    ve.ui.LinearContextItemExtension.getTemplate = function(model) {
        let element = model.element;
        if (!element) return null;
        let attributes = element.attributes;
        if (!attributes) return null;
        let mw = attributes.mw;
        if (!mw) return null;
        let parts = mw.parts && mw.parts.length > 0 && mw.parts[0];
        return parts ? parts.template : null;
    }

    ve.ce.FocusableNodeExtension = {};
    ve.ce.FocusableNodeExtension.extend = function(node, target) {
        let nodes = $('div', node.$highlights);
        if (nodes.eq(0).attr('title') !== '#experimentlist:') {
            return;
        }
        // reduces the width of the overlay on the left side to make the content clickable which is under it
        let widthToReduce = 70;
        nodes.each(function(i, e) {
            let width = $(e).width();
            width = width - widthToReduce;
            $(e).width(width+"px");
            $(e).css({left: widthToReduce+"px", width: width+"px"});
        });
        if ($(target).is('span.experiment-editable')) {
            openPageForms(target);
        }

    }

    let openPageForms = function(targetEl) {
        let target = $(targetEl);
        let form = target.attr('datatype');
        let index = target.attr('resource');
        let name = target.attr('data-x-about');
        let wgPageName = mw.config.get('wgPageName');
        let wgScriptPath = mw.config.get('wgScriptPath');
        ext.popupform.handlePopupFormLink( wgScriptPath + '/Special:FormEdit/'+form + '/' + wgPageName + '/' + name + '?expand=' + index, target );
    }

    let f = function() {
        setTimeout(function() {
           if (!ve.init.target) {
               f();
               return;
           }
            ve.init.target.on('deactivate', function() {
                $('.experiment-editable-column').remove();
            });
        }, 100);
    }
    f();

} );