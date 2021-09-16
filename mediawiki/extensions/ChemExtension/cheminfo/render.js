function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

var smile=getParameterByName('formula');
//var smile="C1=CC=CC=C1";


var smilesDrawer=new SmilesDrawer();
var canvas=document.createElement('canvas');
canvas.setAttribute("style", "display:block")
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
//var canvas=document.getElementsByTagName('canvas')[0];
document.getElementsByTagName('body')[0].appendChild(canvas)



var data = SmilesDrawer.parse(smile);
smilesDrawer.draw(data, canvas);