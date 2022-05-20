
window.onload = function(e){
    let ketcher = window.ketcher;
    if (!ketcher) {
        setTimeout(window.onload, 500);
        return;
    }
    let formula = window.frameElement.getAttribute("formula")
    ketcher.setMolecule(atob(formula));
    renderFormula();

}

function renderFormula() {
    window.ketcher.getSmilesAsync().then(function(smilesFormula) {
        if (smilesFormula == '') {
            setTimeout(function() {
                renderFormula(); },
                500);
            return;
        }
        window.ketcher.generateImageAsync(smilesFormula, { outputFormat: 'svg' }).then(function(svgBlob) {
            const img = new Image();
            img.src = URL.createObjectURL(svgBlob);
            img.style.width = "100%";
            img.style.height = "95%";

            document.body.append(img);
        });
    });
}

