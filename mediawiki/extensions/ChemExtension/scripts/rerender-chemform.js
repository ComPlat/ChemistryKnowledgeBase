(function ($) {

    $(function () {
        let notes = $('.render-formula-note');
        if (notes.length == 0) {
            return;
        }
        let totalRendered = 0;
        OO.ui.confirm("There are molecules on the page which are not yet rendered. This will be done now.").done(function (confirm) {
            if (!confirm) return;
            notes.each(function (i, e) {

                let rerenderNote = OO.ui.infuse(e);
                let inchikey = rerenderNote.data.inchikey;
                let formula = rerenderNote.data.formula;

                let ajax = new window.ChemExtension.AjaxEndpoints();

                ajax.renderImage(formula).then((response) => {
                    let imgData = response.svg;
                    ajax.uploadImage(inchikey, btoa(imgData)).done(function () {
                        totalRendered++;
                        if (totalRendered === notes.length) {
                            location.reload();
                        }
                    });
                }).catch((response) => {
                    console.log("Error on rendering image: " + response.responseText);
                    mw.notify('Problem occured on rendering image: ' + response.responseText, {type: 'error'});
                });

            });

        });

    });

})(jQuery);