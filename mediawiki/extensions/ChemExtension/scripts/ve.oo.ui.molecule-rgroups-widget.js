( function ( OO ) {
    'use strict';

    OO.ui.MoleculeRGroupsWidget = function OoUiMoleculeRGroupsWidget( config ) {
        // Configuration initialization
        config = config || {};

        // Parent constructor
        OO.ui.MoleculeRGroupsWidget.super.call( this, config );

        // Properties
        this.input = config.input;

        // Initialization


    };

    /* Setup */

    OO.inheritClass( OO.ui.MoleculeRGroupsWidget, OO.ui.Widget );

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.MoleculeRGroupsWidget.static.tagName = 'div';

    OO.ui.MoleculeRGroupsWidget.prototype.setData = function(attrs, numberOfMoleculeRGroups, restIds) {

        this.restIds = restIds;
        this.moleculeRGroupsTable = this.readRGroupsAsAttributes(attrs);
        this.numberOfMoleculeRGroups = numberOfMoleculeRGroups;

        this.$element.empty();
        this.table = $('<table>');
        this.table.attr('id', 'molecule-rest');
        let headerRow = this.header();
        this.table.append(headerRow);

        if (this.moleculeRGroupsTable.length === 0) {
            let row = this.newLine();
            this.table.append(row);
        } else {
            for(let i = 0; i < this.moleculeRGroupsTable.length; i++) {
                let row = this.newLine(this.moleculeRGroupsTable[i]);
                this.table.append(row);
            }
        }

        this.$element.append(this.table);

        let newLineButton = new OO.ui.ButtonWidget({
            id: 'new-molecule',
            label: 'New molecule...',
            flags: ['primary', 'progressive']
        });
        newLineButton.on('click', this.addNewLine.bind(this));
        this.$element.append(newLineButton.$element);
    }

    OO.ui.MoleculeRGroupsWidget.prototype.header = function() {
        let row = $('<tr>');
        let firstColumn = $('<th>');
        row.append(firstColumn);
        for(let i = 0; i < this.restIds.length; i++) {
            let column = $('<th>');
            let labelWidget = new OO.ui.LabelWidget( {
                label: this.restIds[i].toUpperCase()
            } );
            column.append(labelWidget.$element);
            row.append(column);
        }
        return row;
    }

    OO.ui.MoleculeRGroupsWidget.prototype.newLine = function(line) {
        let row = $('<tr>');
        row.addClass('molecule-rest');
        let firstColumn = $('<td>');
        let label = $('<span>').append("Molecule").attr('title', 'Each row defines all R-Groups for a molecule');
        firstColumn.append(label);
        row.append(firstColumn);
        for(let i = 0; i < this.restIds.length; i++) {
            let column = $('<td>');
            let textWidget = new OO.ui.RGroupsLookupTextInputWidget( {
                value: line ? line[i] : ''
            } );

            column.append(textWidget.$element);
            row.append(column);
        }
        let removeButtonColumn = $('<td>');
        var removeButton = new OO.ui.ButtonWidget({
            label: 'Remove',
            flags: ['primary', 'destructive']
        });
        removeButton.on('click', function() {
            removeButton.$element.closest('tr').remove();
        });
        removeButtonColumn.append(removeButton.$element);
        row.append(removeButtonColumn);
        return row;
    }

    OO.ui.MoleculeRGroupsWidget.prototype.addNewLine = function() {
        let row = this.newLine();
        this.table.append(row);
    }

    OO.ui.MoleculeRGroupsWidget.prototype.readRGroupsAsAttributes = function(attributes) {

        let rGroupsArray = [];
        for(let i = 0; i < this.restIds.length; i++) {
            if (attributes[this.restIds[i]]) {
                rGroupsArray.push(attributes[this.restIds[i]].split(','))
            }
        }
        return rGroupsArray.length > 0 ? rGroupsArray[0].map((_, colIndex) => rGroupsArray.map(row => row[colIndex])) : [];

    }

    OO.ui.MoleculeRGroupsWidget.prototype.getRGroupsAsAttributes = function() {
        let restArray = this.getRGroupsFromModel();
        let restAttributes = {};
        // transpose array (exchange rows and columns)
        let transposedArray = restArray[0].map((_, colIndex) => restArray.map(row => row[colIndex]));

        for(let i = 0; i < transposedArray.length; i++) {
            restAttributes[this.restIds[i]] = transposedArray[i].join(',');
        }
        return restAttributes;
    }

    OO.ui.MoleculeRGroupsWidget.prototype.getRGroupsFromModel = function() {
        let rows = [];
        $('tr.molecule-rest', this.table).each(function(i, row) {
            let rowEl = $(row);
            let columns = [];
            $('input', rowEl).each(function(j, input) {
                columns.push($(input).val());
            });
            rows.push(columns);
        });
        return rows;
    }
}( OO ) );