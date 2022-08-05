(function($) {

  $(function () {
      let button = $('#render-formula-button');
      if (button.length == 0) {
          return;
      }
      let rerenderButton = OO.ui.infuse(button);
      let inchikey = rerenderButton.data.inchikey;
      let formula = rerenderButton.data.formula;
      button.click(function() {
          let tools = new OO.VisualEditorTools();
          let ketcher = tools.getKetcher();

          ketcher.generateImage(formula, {outputFormat: 'svg'}).then(function (svgBlob) {
              svgBlob.text().then(function (imgData) {

                  tools.uploadImage(inchikey, btoa(imgData), function () {
                      location.reload();

                  });

              });
          });
      })
  });

})(jQuery);