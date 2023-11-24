(function($) {

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.initTooltips = function(container) {

        $('a[title]', container).each(function(i, e) {
            let el = $(e);
            let title = el.attr('title');
            let parts = title.split(":");
            if (parts[0] != 'Molecule') return;
            el.qtip({
                content: "<div></div>",
                style: { classes: 'chemformula-tooltip' },
                events: {

                    render: function(event, api) {
                        // Grab the tooltip element from the API
                        let downloadURL = mw.config.get('wgScriptPath') + "/rest.php/ChemExtension/v1/chemform-by-id?chemFormId="+parts[1]
                        let tooltip = api.elements.tooltip;
                        let tools = new OO.VisualEditorTools();
                        tools.renderFormula(downloadURL, tooltip);

                    }
                },
                position: {
                    viewport: $(window)
                }
            });
        });

    }

    window.ChemExtension.navigate = function(pageDbkey) {
        let namespace = mw.config.get('wgCanonicalNamespace');
        if (namespace == "Reaction" || namespace == "Molecule") {
            return;
        }

        let url = mw.config.get('wgScriptPath')+"/index.php/"+pageDbkey;
        window.open(url, '_blank').focus();
    }

    $(function() {
        window.ChemExtension.initTooltips();
    });

    mw.hook( 'postEdit' ).add(function() {
        window.ChemExtension.initTooltips();
    });



})(jQuery);