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

    });

    function renderFormula(iframe, tooltip) {
        let enc_formula = iframe.attr('smiles');
        if (enc_formula == '') {
            return;
        }
        let smilesFormula = atob(enc_formula);
        ketcher.generateImageAsync(smilesFormula, {outputFormat: 'svg'}).then(function (svgBlob) {
            const img = new Image();
            img.src = URL.createObjectURL(svgBlob);
            img.style.width = "600px";
            img.style.height = "400px";

            tooltip.append(img);
        });
    }

})(jQuery);