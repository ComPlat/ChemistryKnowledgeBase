(function ($) {
    'use strict';


    let initialize = function () {
        function editValue(e) {
            let target = e.target;
            let container = $(target).closest('.experiment-list-container');
            let td = $(target).closest('td');
            let tr = $(target).closest('tr');
            let rowIndex = tr.prop('rowIndex') - 1;
            let cellIndex = td.prop('cellIndex');
            let table = $(target).closest('table.experiment-list');
            let property = table.find('th').eq(cellIndex).attr('about');
            let value = $(target).val();

            td.attr('contenteditable', rowIndex + "|" + property + "|" + value);
            td.empty().text(value);
            activateSaveButton(container);
        }

        function editMoleculeValue(item, widget, sameValue) {
            let target = widget.$element;
            let container = $(target).closest('.experiment-list-container');
            let td = $(target).closest('td');
            let tr = $(target).closest('tr');
            let rowIndex = tr.prop('rowIndex') - 1;
            let cellIndex = td.prop('cellIndex');
            let table = $(target).closest('table.experiment-list');
            let property = table.find('th').eq(cellIndex).attr('about');
            if (!sameValue) {
                td.attr('contenteditable', rowIndex + "|" + property + "|" + item.data);
            }
            td.empty().text(item.label);
            activateSaveButton(container);
        }

        function activateSaveButton(container) {
            let saveButton = OO.ui.infuse(container.find('.experiment-list-save-button'));
            saveButton.setDisabled(false);
            saveButton.off('click');
            saveButton.on('click', () => {
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

        function registerEditFunctionality(e) {
            let table = $(e.target).closest('table.experiment-list');
            let td = $(e.target).closest('td');
            let cellIndex = td.prop('cellIndex');
            let property = table.find('th').eq(cellIndex).attr('about');
            if (property === '-') return;
            let isMolecule = property.startsWith('molecule:');
            let value = $(e.target).text();
            $(e.target).empty();
            let input;
            if (isMolecule) {
                let widget = new OO.ui.MoleculeSelectWidget({'value': value});
                widget.menu.on('choose', (item) => {
                    editMoleculeValue(item, widget, false);
                });
                widget.$input.on('blur', (e) => {
                    let sameValue = $(e.target).val() === value;
                    editMoleculeValue({data: value, 'label': value}, widget, sameValue);
                });
                input = widget.$element;
                $(e.target).append(input);
                widget.$input.focus();
                widget.$input.select();
            } else {
                input = $('<input type="text">').val(value);
                input.blur((e) => {
                    editValue(e);
                });
                input.keypress((e) => {
                    if (e.which === 13) {
                        editValue(e);

                    }
                });
                $(e.target).append(input);
            }
            input.focus().select();
        }

        $('.experiment-list td')
            .off('click')
            .click((e) => {
                registerEditFunctionality(e);
            });

        $('.experiment-list-container').each((i, container) => {

            let newLine = $("<img>")
                .addClass('ce-insert-new-line')
                .attr('src', mw.config.get('wgScript') + '/extensions/ChemExtension/skins/images/plus.png');

            let table =  $(container).find('table.experiment-list');
            newLine.click((e) => {
                let tr = $('<tr>');

                for (let i = 0; i < table.find('th').length+1; i++) {

                    let cell = $('<td>');
                    if (i === 0) {
                        cell.text("-new line-");
                    }
                    tr.append(cell);
                }
                table.append(tr);
                activateSaveButton(table.closest('.experiment-list-container'));
                table.off('click').click((e) => { registerEditFunctionality(e); });
            });

            table.parent().append(newLine);

        });
    };

    let deleteChanges = function (container) {
        container.find('td[contenteditable]').removeAttr('contenteditable');
    }

    let collectChanges = function (container) {

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
        if (!mw.user) return;
        mw.user.getGroups().done((groups) => {
            if (!groups.includes('editor')) return;
            initialize();
        })

    });


}(jQuery));