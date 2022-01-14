(function($) {
    let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/WikiFarm";

    window.WikiFarm = window.WikiFarm || {};
    window.WikiFarm.Ajax = function() {

        let that = {};

        that.updateWikiTable = function() {
            let url = baseUrl + "/v1/wikis";
            return $.ajax({
                method: "GET",
                url: url
            });
        }

        that.createWiki = function(wikiName) {
            let url = baseUrl + "/v1/wikis";

            return $.ajax({
                method: "POST",
                url: url,
                contentType: "application/x-www-form-urlencoded",
                data: {
                    'wikiName' : wikiName
                }
            });
        }

        that.removeWiki = function(wikiId) {
            let url = baseUrl + "/v1/wikis/"+wikiId;

            return $.ajax({
                method: "DELETE",
                url: url
            });
        }

        that.updateUsersOfWiki = function(wikiId, users) {
            let url = baseUrl + "/v1/wikis/"+wikiId+"/users";

            return $.ajax({
                method: "POST",
                url: url,
                contentType: "application/json",
                data: JSON.stringify({
                    'users' : users
                })
            });
        }

        that.getUsersOfWiki = function(wikiId) {
            let url = baseUrl + "/v1/wikis/"+wikiId+"/users";

            return $.ajax({
                method: "GET",
                url: url
            });
        }

        return that;
    }

}(jQuery));