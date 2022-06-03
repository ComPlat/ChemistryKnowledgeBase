
window.onload = function(e){

    if (!window.parent.ketcher) {
        setTimeout(window.onload, 100);
        return;
    }

    let enc_formula = window.frameElement.getAttribute("smiles")
    if (enc_formula == '') {
        console.log("formula empty")
        return;
    }

    let smilesFormula = atob(enc_formula);

    render();

    function render() {
        if (imgAlreadyRendered()) {
            return;
        }
        window.parent.ketcher.generateImage(smilesFormula, {outputFormat: 'svg'}).then(function (svgBlob) {
            if (imgAlreadyRendered()) {
                return;
            }
            const img = new Image();
            img.src = URL.createObjectURL(svgBlob);
            img.style.width = "100%";
            img.style.height = "95%";

            document.body.append(img);

        });

        setTimeout(render, 100);

    }

    function imgAlreadyRendered() {
        return document.getElementsByTagName('img').length > 0;
    }
}


