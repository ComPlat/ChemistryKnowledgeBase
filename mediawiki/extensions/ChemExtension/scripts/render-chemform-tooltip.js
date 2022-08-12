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
            },
            position: {
                at: 'top right'
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
        let downloadURL = decodeURIComponent(iframe.attr('downloadurl'));
        if (downloadURL == '') {
            return;
        }
        fetch(downloadURL).then(r => {

            if (r.status != 200) {
                image.append('Image does not exist. Please re-save in editor.');
                return;
            }
            r.blob().then(function (blob) {
                const img = new Image();
                img.src = URL.createObjectURL(blob);
                img.style.width = "100%";
                img.style.height = "95%";

                tooltip.append(img);

            });

        });

    }

})(jQuery);