(function ($) {
    'use strict';


    let initialize = function() {
        function editValue(e) {
            let container = $(e.target).closest('.experiment-list-container');
            let value = $(e.target).val();
            let td = $(e.target).closest('td');
            let tr = $(e.target).closest('tr');
            let rowIndex = tr.prop('rowIndex');
            let table = $(e.target).closest('table.experiment-list');
            let property = table.find('th').eq(rowIndex).text();

            td.attr('contenteditable', rowIndex + "|" + property + "|" + value);
            td.empty().text(value);
            activateSaveButton(container);
        }

        function activateSaveButton(container) {
            let saveButton = OO.ui.infuse(container.find('.experiment-list-save-button'));
            saveButton.setDisabled(false);
            saveButton.off('click');
            saveButton.on('click', () =>{
                let buttonElement = saveButton.$element.find('button')
                let request = JSON.parse(buttonElement.attr('value'));
                let ajax = new window.ChemExtension.AjaxEndpoints();
                request.changes = collectChanges(container);
                ajax.editExperiment(request).done((response) => {
                    saveButton.setDisabled(true);
                    deleteChanges(container);
                    mw.notify('Changes saved');
                }).fail((e) => {
                    mw.notify('Saving failed');
                });
            });
        }

        $('.experiment-list td')
            .off('click')
            .click((e) => {
            let value = $(e.target).text();
            $(e.target).empty();
            let input = $('<input type="text">').val(value);
            input.blur((e) => {
                editValue(e);
            });
            input.keypress((e) => {
                if (e.which === 13) {
                    editValue(e);

                }
            });
            $(e.target).append(input);
            input.focus().select();
        });

    };

    let deleteChanges = function(container) {
       container.find('td[contenteditable]').removeAttr('contenteditable');
    }

    let collectChanges = function(container) {

        let rows = container.find('td[contenteditable]');
        let changes = [];
        rows.each((i, e) => {
            let content = $(e).attr('contenteditable');
            let parts = content.split('|');
            changes.push({
                row: parts[0],
                property: parts[1],
                value: parts[2]
            })
        });
        return changes;
    }

    $(function () {
        initialize();
    });


}(jQuery));