
(function($) {


    let baseUrl = mw.config.get("wgScriptPath") + "/rest.php/WikiFarm";

    var updateWikiTable = function() {
        let url = baseUrl + "/v1/wikis";
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
        let url = baseUrl + "/v1/wikis";

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

    var updateUsersOfWiki = function(wikiId, users, callback) {
        let url = baseUrl + "/v1/wikis/"+wikiId+"/users";

        $.ajax({
            method: "POST",
            url: url,
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify({
                'users' : users
            })
        }).done(function( msg ) {
            console.log(msg);
            callback(msg);
        }).error(function(msg) {
            console.log(msg);
            callback(msg);
        });
    }

    var getUsersOfWiki = function(wikiId, callback) {
        let url = baseUrl + "/v1/wikis/"+wikiId+"/users";

        $.ajax({
            method: "GET",
            url: url
        }).done(function( msg ) {
            console.log(msg);
            callback(msg);
        }).error(function(msg) {
            console.log(msg);
            callback(msg);
        });
    }

    $(function() {

        var selectedWiki = null;
        var createWikiButton = OO.ui.infuse($('#wfarm-create-wiki'));
        createWikiButton.setDisabled(true);
        var saveUserButton =  OO.ui.infuse($('#wfarm-add-user'));

        $('#wfarm-wikiName input').keyup(function(e) {
            createWikiButton.setDisabled($(e.target).val() == '');
        });
        $('#wfarm-create-wiki').click(function() {
            let wikiName = $('#wfarm-wikiName input').val();
            createWiki(wikiName);
        });

        let usersMultiselectWidget = new mw.widgets.UsersMultiselectWidget({});
        usersMultiselectWidget.allowArbitrary = true;


        $('#wfarm-wikiUserList').append( usersMultiselectWidget.$element);

        $('#wfarm-add-user').click(function(){
            let userNames = usersMultiselectWidget.getSelectedUsernames();
            if (selectedWiki == null) return;
            usersMultiselectWidget.pushPending();
            updateUsersOfWiki(selectedWiki, userNames, function() {
                usersMultiselectWidget.popPending();
            });
        });

        $('#wfarm-createdwikis-table tr').click(function(e) {
           let target = e.target;
           let row = $(target).parent();
           let wikiId = row.attr('wiki-id');
           if (wikiId == null) {
               return;
           }
            $('#wfarm-createdwikis-table tr').each(function(i, e) {
                $(e).removeClass('wfarm-table-selected');
            });
           row.addClass('wfarm-table-selected');
           $('#wfarm-wikiUserList-section').show();

           selectedWiki = wikiId;
           usersMultiselectWidget.pushPending();
            saveUserButton.setDisabled(true);
           getUsersOfWiki(wikiId, function(response) {
               usersMultiselectWidget.onChangeTags = function(e) {

               };
               usersMultiselectWidget.popPending();
               usersMultiselectWidget.clearItems();
                for(let i = 0; i < response.users.length; i++) {
                    usersMultiselectWidget.addTag(response.users[i], response.users[i]);
                }
               usersMultiselectWidget.onChangeTags = function(e) {
                   saveUserButton.setDisabled(false);
               };
            });
        });
    })
}(jQuery));

