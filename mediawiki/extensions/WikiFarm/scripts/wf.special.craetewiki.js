
(function($) {
    $(function() {
        console.log("Here I am: SpecialCreateWiki");
        $('#create-wiki').click(function() {
            let url = mw.config.get("wgScriptPath") + "/rest.php/WikiFarm/v1/createWiki";

            $.ajax({
                method: "POST",
                url: url,
                contentType: "application/x-www-form-urlencoded",
                data: "wikiName=PersonalWiki"
            }).done(function( msg ) {
                    console.log(msg);
            });
        });
    })
}(jQuery));

