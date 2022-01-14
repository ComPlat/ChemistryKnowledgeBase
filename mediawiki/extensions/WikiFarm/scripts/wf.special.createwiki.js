(function ($) {


    let Ajax = new window.WikiFarm.Ajax();

    $(function () {

        let selectedWiki = null;
        let createWikiButton = OO.ui.infuse($('#wfarm-create-wiki'));
        createWikiButton.setDisabled(true);
        let saveUserButton = OO.ui.infuse($('#wfarm-add-user'));

        $('#wfarm-wikiName input').keyup(function (e) {
            if (e.keyCode != 13) {
                createWikiButton.setDisabled($(e.target).val() == '');
            }
        });
        $('#wfarm-wikiName input').keypress(function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                if (!createWikiButton.isDisabled()) {
                    let wikiName = $('#wfarm-wikiName input').val();
                    createWiki(wikiName);
                }
            }
        });
        $('#wfarm-create-wiki').click(function () {
            let wikiName = $('#wfarm-wikiName input').val();
            createWiki(wikiName);
        });

        let usersMultiselectWidget = new mw.widgets.UsersMultiselectWidget({});
        usersMultiselectWidget.allowArbitrary = true;


        $('#wfarm-wikiUserList').append(usersMultiselectWidget.$element);

        $('#wfarm-add-user').click(function () {
            let userNames = usersMultiselectWidget.getSelectedUsernames();
            if (selectedWiki == null) return;
            usersMultiselectWidget.pushPending();
            Ajax.updateUsersOfWiki(selectedWiki, userNames).done(function (response) {
                usersMultiselectWidget.popPending();
            }).error(function (msg) {
                console.log("Error WikiFarm: " + msg.responseText);
                mw.notify(mw.message('wfarm-ajax-error').text());
            });

        });

        let createWiki = function(wikiName) {
            createWikiButton.setDisabled(true);
            Ajax.createWiki(wikiName).done(function (msg) {
                updateTable(function() {
                    createWikiButton.setDisabled(false);
                });
            }).error(function (msg) {
                createWikiButton.setDisabled(false);
                console.log("Error WikiFarm: " + msg.responseText);
                mw.notify(mw.message('wfarm-ajax-error').text());
            });
            ;
        }

        let registerTableListeners = function() {

            $('#wfarm-createdwikis-table tr').click(function (e) {
                let target = e.target;
                let row = $(target).parent();
                let wikiId = row.attr('wiki-id');
                if (wikiId == null) {
                    return;
                }
                $('#wfarm-createdwikis-table tr').each(function (i, e) {
                    $(e).removeClass('wfarm-table-selected');
                });
                row.addClass('wfarm-table-selected');
                $('#wfarm-wikiUserList-section').show();

                selectedWiki = wikiId;
                usersMultiselectWidget.pushPending();
                saveUserButton.setDisabled(true);
                Ajax.getUsersOfWiki(wikiId).done(function (response) {
                    usersMultiselectWidget.onChangeTags = function (e) {

                    };
                    usersMultiselectWidget.popPending();
                    usersMultiselectWidget.clearItems();
                    for (let i = 0; i < response.users.length; i++) {
                        usersMultiselectWidget.addTag(response.users[i], response.users[i]);
                    }
                    usersMultiselectWidget.onChangeTags = function (e) {
                        saveUserButton.setDisabled(false);
                    };
                }).error(function (msg) {
                    console.log("Error WikiFarm: " + msg.responseText);
                    mw.notify(mw.message('wfarm-ajax-error').text());
                });

            });

            $('.wfarm-remove-wiki').click(function(e) {
                let target = e.target;
                let button = $(target).closest('span.wfarm-remove-wiki');
                let oouiButton = OO.ui.infuse(button);
                let id = button.attr('wiki-id');
                OO.ui.confirm(mw.message('wfarm-remove-wiki-confirm').text()).done(function(confirm) {
                    if (!confirm) return;
                    oouiButton.setDisabled(true);
                    Ajax.removeWiki(id).done(function () {
                        updateTable();
                    }).error(function (msg) {
                        oouiButton.setDisabled(false);
                        console.log("Error WikiFarm: " + msg.responseText);
                        mw.notify(mw.message('wfarm-ajax-error').text());
                    });
                });

            });
        };

        let updateTable = function(callbackOnSuccess) {
            Ajax.updateWikiTable().done(function (msg) {
                $('#wfarm-createdwikis-table').replaceWith(msg.html);
                registerTableListeners();
                $('#wfarm-wikiUserList-section').hide();
                if (callbackOnSuccess) callbackOnSuccess();
            }).error(function (msg) {
                console.log("Error WikiFarm: " + msg.responseText);
                mw.notify(mw.message('wfarm-ajax-error').text());
            });
        }

        registerTableListeners();
    })
}(jQuery));

