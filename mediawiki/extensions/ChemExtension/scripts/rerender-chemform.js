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

                let tools = new OO.VisualEditorTools();
                let ajax = new window.ChemExtension.AjaxEndpoints();
                let ketcher = tools.getKetcher();

                ketcher.generateImage(formula, {outputFormat: 'svg'}).then(function (svgBlob) {
                    svgBlob.text().then(function (imgData) {

                        ajax.uploadImage(inchikey, btoa(imgData), function () {
                            totalRendered++;
                            if (totalRendered === notes.length) {
                                location.reload();
                            }
                        });

                    });
                });

            });

        });

    });

})(jQuery);