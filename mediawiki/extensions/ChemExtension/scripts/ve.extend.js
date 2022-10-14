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
                formula: molfile,
                smiles: smiles,
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
            let documentNode = ve.init.target.getSurface().getView().getDocument().getDocumentNode();
            let iterate = function(node) {
                for(let i = 0; i < node.children.length; i++) {
                    let child = node.children[i];
                    if (child.type === 'mwTransclusionBlock' || child.type === 'mwTransclusionInline') {
                        child.forceUpdate();
                    }
                    iterate(child);
                }
            }
            iterate(documentNode);
        });

        addButton.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            if (!template || !template.params || !template.params.form) return;
            let form = template.params.form.wt;
            let wgScriptPath = mw.config.get('wgScriptPath');
            let wgPageName = mw.config.get('wgPageName');
            ext.popupform.handlePopupFormLink( wgScriptPath + '/Special:FormEdit/'+form + '/' + wgPageName + '/' + form, addButton.$element );
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
    ve.ce.FocusableNodeExtension.extend = function(node) {
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
        $('div.ve-ce-focusableNode span.experiment-editable').off('click');
        $('div.ve-ce-focusableNode span.experiment-editable').click(function(e) {
            let target = $(e.target);
            let form = target.attr('datatype');
            let index = target.attr('resource');
            let wgPageName = mw.config.get('wgPageName');
            let wgScriptPath = mw.config.get('wgScriptPath');
            ext.popupform.handlePopupFormLink( wgScriptPath + '/Special:FormEdit/'+form + '/' + wgPageName + '/' + form + '?expand=' + index, target );
        });
    }

    let f = function() {
        setTimeout(function() {
           if (!ve.init.target) {
               f();
           }
            ve.init.target.on('deactivate', function() {
                $('.experiment-editable-column').remove();
            });
        }, 100);
    }
    f();

} );