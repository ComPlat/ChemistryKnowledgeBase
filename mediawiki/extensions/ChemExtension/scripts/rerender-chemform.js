(function($) {

  $(function () {
      let note = $('#render-formula-note');
      if (note.length == 0) {
          return;
      }
      let rerenderNote = OO.ui.infuse(note);
      let inchikey = rerenderNote.data.inchikey;
      let formula = rerenderNote.data.formula;
      OO.ui.confirm("The molecule was not yet rendered. This will be done now.").done(function(confirm) {
          if (!confirm) return;
          let tools = new OO.VisualEditorTools();
          let ajax = new window.ChemExtension.AjaxEndpoints();
          let ketcher = tools.getKetcher();

          ketcher.generateImage(formula, {outputFormat: 'svg'}).then(function (svgBlob) {
              svgBlob.text().then(function (imgData) {

                  ajax.uploadImage(inchikey, btoa(imgData), function () {
                      location.reload();

                  });

              });
          });
      });

  });

})(jQuery);