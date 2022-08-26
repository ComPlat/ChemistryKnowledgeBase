window.onload = function (e) {

    let chemFormPageText = window.frameElement.getAttribute("chemFormPageText");
    let downloadURL = decodeURIComponent(window.frameElement.getAttribute("downloadurl"));
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


            var myDialog = new window.parent.ChemExtension.ShowGroupsDialog( {
                size: 'large'
            } );

            let windowManager = new window.parent.OO.ui.WindowManager();
            window.parent.document.body.append( windowManager.$element['0']);

            windowManager.addWindows( [ myDialog ] );
            windowManager.openWindow( myDialog, {moleculeKey: moleculeKey, pageid: pageid} );

        });
    }

}


