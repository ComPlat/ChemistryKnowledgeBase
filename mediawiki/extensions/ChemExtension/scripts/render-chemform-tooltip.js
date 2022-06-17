(function($) {
    $(function() {
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

        $('iframe.chemformula').each(function(i,e) {
            let target = $(e);
            let id = target.attr('chemformid');
            let isreaction = target.attr('isreaction') == 'true';
            let namespaceName = isreaction ? "Reaction" : "Molecule";
            $('div', e.contentWindow.document.body).click(function(el) {
                let url = mw.config.get('wgScriptPath')+"/index.php/"+namespaceName+":"+namespaceName+"_"+id;
                window.open(url, '_blank').focus();
            });
        });

    });

    function renderFormula(iframe, tooltip) {
        let enc_formula = iframe.attr('formula');
        if (enc_formula == '') {
            return;
        }
        let smilesFormula = atob(enc_formula);
        ketcher.generateImage(smilesFormula, {outputFormat: 'svg'}).then(function (svgBlob) {
            const img = new Image();
            img.src = URL.createObjectURL(svgBlob);
            img.style.width = "600px";
            img.style.height = "400px";

            tooltip.append(img);
        });
    }

})(jQuery);