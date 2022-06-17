
window.onload = function(e){

    if (!window.parent.ketcher) {
        setTimeout(window.onload, 100);
        return;
    }

    let enc_formula = window.frameElement.getAttribute("formula")
    if (enc_formula == '') {
        console.log("formula empty")
        return;
    }

    let chemFormId = window.frameElement.getAttribute("chemFormId");
    let isReaction = window.frameElement.getAttribute("isreaction") == 'true';

    let formula = atob(enc_formula);

    if (formula.indexOf('$RXN') !== -1) {
        formula = formula.substr(formula.indexOf('$RXN'));
    }

    render();


    function render() {
        if (imgAlreadyRendered()) {
            return;
        }
        window.parent.ketcher.generateImage(formula, {outputFormat: 'svg'}).then(function (svgBlob) {
            if (imgAlreadyRendered()) {
                return;
            }
            const img = new Image();
            img.src = URL.createObjectURL(svgBlob);
            img.style.width = "100%";
            img.style.height = "95%";

            let image = document.getElementById("image");
            image.append(img);

            if (chemFormId != null && chemFormId != '') {
                let caption = document.getElementById("caption");
                let label = isReaction ? "Reaction" : "Molecule";
                caption.append(label + " " + chemFormId);
            }
        });


        setTimeout(render, 100);


    }

    function imgAlreadyRendered() {
        return document.getElementsByTagName('img').length > 0;
    }
}


