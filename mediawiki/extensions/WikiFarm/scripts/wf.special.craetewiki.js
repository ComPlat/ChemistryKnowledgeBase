
(function($) {
    $(function() {
        console.log("Here I am: SpecialCreateWiki");
        $('#create-wiki').click(function() {
            let url = mw.config.get("wgScriptPath") + "/rest.php/WikiFarm/v1/createWiki";

            $.ajax({
                method: "POST",
                url: url,
                contentType: "application/x-www-form-urlencoded",
                data: "wikiId=wiki11&wikiName=Wiki11"
            }).done(function( msg ) {
                    console.log(msg);
            });
        });
    })
}(jQuery));

