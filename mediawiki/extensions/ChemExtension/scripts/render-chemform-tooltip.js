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
                    renderFormula(iframe, tooltip);

                }
            }
        });

    }

    window.ChemExtension.navigate = function(id, isreaction) {
        let namespace = mw.config.get('wgCanonicalNamespace');
        if (namespace == "Reaction" || namespace == "Molecule") {
            return;
        }

        let namespaceName = isreaction == 'true' ? "Reaction" : "Molecule";
        let url = mw.config.get('wgScriptPath')+"/index.php/"+namespaceName+":"+namespaceName+"_"+id;
        window.open(url, '_blank').focus();
    }

    $(function() {
        window.ChemExtension.initTooltips();
    });

    mw.hook( 'postEdit' ).add(function() {
        window.ChemExtension.initTooltips();
    });

    function renderFormula(iframe, tooltip) {
        let enc_formula = iframe.attr('formula');
        if (enc_formula == '') {
            return;
        }
        let formula = atob(enc_formula);

        if (formula.indexOf('$RXN') !== -1) {
            formula = formula.substr(formula.indexOf('$RXN'));
        }
        ketcher.generateImage(formula, {outputFormat: 'svg'}).then(function (svgBlob) {
            const img = new Image();
            img.src = URL.createObjectURL(svgBlob);
            img.style.width = "600px";
            img.style.height = "400px";

            tooltip.append(img);
        });
    }

})(jQuery);