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
        } else if (template.target.wt.trim() == '#veforminput:') {
            ve.ui.LinearContextItemExtension.extendForVEFormInput(panel, context, model);
        }
    }

    ve.ui.LinearContextItemExtension.extendForVEFormInput = function(panel, context, model) {
        let button = new OO.ui.ButtonWidget({
            label: 'Add experiment'
        });

        button.on('click', function () {
            let template = ve.ui.LinearContextItemExtension.getTemplate(model);
            if (!template || !template.params || !template.params.form) return;
            let form = template.params.form.wt;
            let wgScriptPath = mw.config.get('wgScriptPath');
            let wgPageName = mw.config.get('wgPageName');
            ext.popupform.handlePopupFormLink( wgScriptPath + '/Special:FormEdit/'+form + '/' + wgPageName + '/' + form, button.$element );

        });

        panel.$actions.append(button.$element);
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
} );