
window.onload = function(e){
    var ketcher = window.ketcher;
    var formula = window.frameElement.getAttribute("formula")
    ketcher.setMolecule(formula);
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
            /*let width = window.frameElement.getAttribute('width');
            let height = window.frameElement.getAttribute('height');
            document.body.style['min-width'] = width;*/
            document.body.append(img);
        });
    });
}

