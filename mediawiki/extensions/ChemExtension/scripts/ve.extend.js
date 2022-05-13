mw.loader.using('ext.visualEditor.core').then(function () {

    ve.ui.MWAlienExtensionInspectorExtension = {};

    ve.ui.MWAlienExtensionInspectorExtension.extend = function(panel) {
        if (panel.originalMwData.name != 'chemform') {
            return;
        }

        var chemForm = panel.originalMwData.body.extsrc;
        var id = panel.originalMwData.attrs.id;
        var button = new OO.ui.ButtonWidget({
            label: 'Open Ketcher'
        });

        button.on('click', function () {
            ve.init.target.getSurface().execute('window', 'open', 'edit-with-ketcher', {
                formula: chemForm,
                id: id
            });
        });

        panel.$attributes.append(button.$element);

        panel.title.setLabel(panel.selectedNode.getExtensionName());
    }

} );