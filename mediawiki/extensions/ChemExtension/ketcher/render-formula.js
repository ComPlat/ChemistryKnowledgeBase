window.onload = function (e) {

    let chemFormId = window.frameElement.getAttribute("chemFormId");
    let isReaction = window.frameElement.getAttribute("isreaction") == 'true';
    let downloadURL = decodeURIComponent(window.frameElement.getAttribute("downloadurl"));

    render();

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

                if (chemFormId != null && chemFormId != '') {
                    let caption = document.getElementById("caption");
                    let label = isReaction ? "Reaction" : "Molecule";
                    caption.append(label + " " + chemFormId);
                }
            });

        });


    }

}


