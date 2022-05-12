function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

onRuntimeInitialized: initRDKitModule().then(function(instance) {
    console.log('RDKIT version: ' + instance.version());
    var formula=getParameterByName('formula');
    if (formula.indexOf("Ketcher") !== -1) {
        formula = "\n" + formula.substr(formula.indexOf("Ketcher"));
    }
    formula =formula.replace(/^[\n\s]+/,"\n");
    formula =formula.replace(/[\n\s]+$/,"\n");
    var mol = instance.get_mol(formula);

    var details = {};
    var tdetails = JSON.stringify(details)
    var svg = mol.get_svg_with_highlights(tdetails);
    if (svg == "") return;
    var canvas = document.getElementById("rdkit-canvas");
    mol.draw_to_canvas_with_highlights(canvas, tdetails);
});



