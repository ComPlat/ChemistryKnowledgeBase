mw.loader.using('ext.visualEditor.core').then(function () {

    ve.ui.MWAlienExtensionInspectorExtension = {};

    ve.ui.MWAlienExtensionInspectorExtension.extend = function(panel) {
        if (panel.originalMwData.name != 'chemform') {
            return;
        }
        let tools = new OO.VisualEditorTools();

        var chemForm = panel.originalMwData.body.extsrc;

        var button = new OO.ui.ButtonWidget({
            label: 'Open Ketcher'
        });

        button.on('click', function () {
            ve.init.target.getSurface().execute('window', 'open', 'edit-with-ketcher', {
                formula: chemForm,
                node: panel.selectedNode
            });
        });

        panel.$attributes.append(button.$element);

        button = new OO.ui.ButtonWidget({
            label: 'Define R-Groups',
            disabled: tools.getNumberOfMoleculeRests(chemForm) === 0
        });

        button.on('click', function () {
            ve.init.target.getSurface().execute('window', 'open', 'edit-molecule-rests', {
                attrs: panel.originalMwData.attrs,
                numberOfMoleculeRests: tools.getNumberOfMoleculeRests(chemForm),
                restIds: tools.getRestIds(chemForm),
                node: panel.selectedNode
            });
        });

        panel.$attributes.append(button.$element);

        panel.title.setLabel(panel.selectedNode.getExtensionName());
    }

    ve.ui.LinearContextItemExtension = {};
    ve.ui.LinearContextItemExtension.extend = function(panel, context, model) {

        let template = ve.ui.LinearContextItemExtension.getTemplate(model);
        if (template == null || template.target == null || template.target.wt != '#literature:') {
            return;
        }

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