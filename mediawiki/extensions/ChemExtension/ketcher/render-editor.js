
window.parent.onload = function(e){
    var ketcher = window.ketcher;

    var formula = window.frameElement.getAttribute('formula');
    if (!formula.startsWith("$RXN")) {
        if (!formula.startsWith("\n")) {
            formula = "\n" + formula;
        }
        if (!formula.endsWith("\n")) {
            formula = formula + "\n";
        }
    }


    ketcher.setMolecule(formula);
}

window.onload = window.parent.onload;


