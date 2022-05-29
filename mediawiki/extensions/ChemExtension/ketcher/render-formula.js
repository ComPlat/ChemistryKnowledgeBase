
window.onload = function(e){
    let ketcher = window.parent.ketcher;
    if (!ketcher) {
        setTimeout(window.onload, 500);
        return;
    }
    let enc_formula = window.frameElement.getAttribute("smiles")
    if (enc_formula == '') {
        return;
    }
    let smilesFormula = atob(enc_formula);
    ketcher.generateImageAsync(smilesFormula, { outputFormat: 'svg' }).then(function(svgBlob) {
        const img = new Image();
        img.src = URL.createObjectURL(svgBlob);
        img.style.width = "100%";
        img.style.height = "95%";

        document.body.append(img);
    });
}


