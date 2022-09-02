
window.onload = function(e){

    let formula = window.frameElement.getAttribute('formula');
    if (!formula.startsWith("$RXN")) {
        if (formula.trim() === '') {
            // try smiles
            formula = window.frameElement.getAttribute('smiles');
        } else {
            if (!formula.startsWith("\n")) {
                formula = "\n" + formula;
            }
            if (!formula.endsWith("\n")) {
                formula = formula + "\n";
            }
        }
    }

    let f = function() {
        if (window.ketcher) {
            window.ketcher.setMolecule(formula);
        } else {
            setTimeout(f, 100);
        }
    }
    f();
}




