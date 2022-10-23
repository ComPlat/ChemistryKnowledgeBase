window.onload = function (e) {

    let chemFormPageText = window.frameElement.getAttribute("chemFormPageText");
    let downloadURL = window.frameElement.getAttribute("downloadurl");
    let showrgroups = window.frameElement.getAttribute("showrgroups") == 'true';

    render();
    addRGroupsButtonListener();

    function render() {

        fetch(downloadURL).then(r => {
            let image = document.getElementById("image");
            if (r.status != 200) {
                image.append('Image does not exist. Please re-save in editor.');
                return;
            }
            r.blob().then(function (blob) {
                const img = new Image();
                img.src = URL.createObjectURL(blob);
                img.style.width = "100%";
                img.style.height = "95%";

                image.append(img);

                if (chemFormPageText != null && chemFormPageText != '') {
                    let caption = document.getElementById("caption");
                    caption.append(chemFormPageText);
                }
            });

        });


    }

    function addRGroupsButtonListener() {
        if (!showrgroups) {
            return;
        }
        let rGroupsBtn = document.getElementById("show_rgroups");
        rGroupsBtn.append("[Show R-Groups]");
        rGroupsBtn.addEventListener('click', function (event) {
            let moleculeKey = getParameterByName('moleculekey');
            let pageid = getParameterByName('pageid');

            let $ = window.parent.$;
            let draggable = $('<div>').addClass('ui-widget-content rgroup-draggable');
            let myDialog = new window.parent.ChemExtension.ShowGroupsDialog( {
                size: 'large'
            }, draggable );
            myDialog.initialize({moleculeKey: moleculeKey, pageid: pageid});

            draggable.css({
                top: getScrollPos() + Math.floor((window.parent.innerHeight - 450) / 2),
                left: Math.floor((window.parent.innerWidth - 1000) / 2)
            });
            draggable.draggable();
            ;
            $('body').prepend($('<div>').height('0px').append(draggable));

        });
    }

    function getScrollPos() {
        let parent = window.parent;
        let doc = parent.document.documentElement;
        return (parent.pageYOffset || doc.scrollTop)  - (doc.clientTop || 0);

    }

}


