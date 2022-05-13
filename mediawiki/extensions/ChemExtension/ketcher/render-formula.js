function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function getKetcherFrameElement(base) {
    for(var i = 0; i < base.frames.length; i++) {
        if (base.frames[i].window.ketcher) {
            return base.frames[i].frameElement;
        }
    }
    console.log("Ketcher not found");
    return null;
}

window.parent.onload = function(e){
    var ketcher = window.ketcher;
    var formula = getKetcherFrameElement(window.parent).getAttribute("formula")
    //var smile=getParameterByName('chemform');
    ketcher.setMolecule(formula);
}

window.onload = window.parent.onload;
