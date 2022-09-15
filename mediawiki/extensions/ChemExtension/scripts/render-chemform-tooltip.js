(function($) {

    window.ChemExtension = window.ChemExtension || {};
    window.ChemExtension.initTooltips = function() {
        $('iframe.chemformula').qtip({
            content: "<div></div>",
            style: { classes: 'chemformula-tooltip' },
            events: {

                render: function(event, api) {
                    // Grab the tooltip element from the API
                    let tooltip = api.elements.tooltip;
                    let iframe = api.elements.target;
                    let downloadURL = iframe.attr('downloadurl');
                    let tools = new OO.VisualEditorTools();
                    tools.renderFormula(downloadURL, tooltip);

                }
            },
            position: {
                at: 'top right'
            }
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