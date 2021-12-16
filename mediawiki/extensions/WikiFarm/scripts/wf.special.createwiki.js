
(function($) {

    let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/WikiFarm";

    var updateWikiTable = function() {
        let url = baseUrl + "/v1/getWikis";
        $.ajax({
            method: "GET",
            url: url
        }).done(function( msg ) {
            $('#wfarm-createdwikis-table').html(msg.html);
        }).error(function(msg) {
            console.log(msg);
        });
    }

    var createWiki = function(wikiName) {
        let url = baseUrl + "/v1/createWiki";

        $.ajax({
            method: "POST",
            url: url,
            contentType: "application/x-www-form-urlencoded",
            data: {
                'wikiName' : wikiName
            }
        }).done(function( msg ) {
            console.log(msg);
            updateWikiTable();
        }).error(function(msg) {
            console.log(msg);
        });
    }

    $(function() {
        $('#chemextension-create-wiki').click(function() {
            let wikiName = $('#wfarm-wikiName input').val();
            createWiki(wikiName);
        });
    })
}(jQuery));

