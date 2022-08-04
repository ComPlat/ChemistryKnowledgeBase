( function ( OO ) {
    'use strict';

    OO.ui.MoleculeRestsWidget = function OoUiMoleculeRestsWidget( config ) {
        // Configuration initialization
        config = config || {};

        // Parent constructor
        OO.ui.MoleculeRestsWidget.super.call( this, config );

        // Properties
        this.input = config.input;

        // Initialization


    };

    /* Setup */

    OO.inheritClass( OO.ui.MoleculeRestsWidget, OO.ui.Widget );

    /* Static Properties */

    /**
     * @static
     * @inheritdoc
     */
    OO.ui.MoleculeRestsWidget.static.tagName = 'div';

    OO.ui.MoleculeRestsWidget.prototype.setData = function(attrs, id, numberOfMoleculeRests, restIds) {

        this.id = id;
        this.restIds = restIds;
        this.moleculeRestTable = this.readRestsAsAttributes(attrs);
        this.numberOfMoleculeRests = numberOfMoleculeRests;

        this.$element.empty();
        this.table = $('<table>');
        let headerRow = this.header();
        this.table.append(headerRow);

        if (this.moleculeRestTable.length === 0) {
            let row = this.newLine();
            this.table.append(row);
        } else {
            for(let i = 0; i < this.moleculeRestTable.length; i++) {
                let row = this.newLine(this.moleculeRestTable[i]);
                this.table.append(row);
            }
        }

        this.$element.append(this.table);

        let newLineButton = new OO.ui.ButtonWidget({
            label: 'New line...',
            flags: ['primary', 'progressive']
        });
        newLineButton.on('click', this.addNewLine.bind(this));
        this.$element.append(newLineButton.$element);
    }

    OO.ui.MoleculeRestsWidget.prototype.header = function() {
        let row = $('<tr>');
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

    OO.ui.MoleculeRestsWidget.prototype.newLine = function(line) {
        let row = $('<tr>');
        row.addClass('molecule-rest')
        for(let i = 0; i < this.restIds.length; i++) {
            let column = $('<td>');
            let textWidget = new ve.ui.WhitespacePreservingTextInputWidget( {
                autosize: true,
                valueAndWhitespace: line ? line[i] : ''
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

    OO.ui.MoleculeRestsWidget.prototype.addNewLine = function() {
        let row = this.newLine();
        this.table.append(row);
    }

    OO.ui.MoleculeRestsWidget.prototype.readRestsAsAttributes = function(attributes) {

        let restsArray = [];
        for(let i = 0; i < this.restIds.length; i++) {
            if (attributes[this.restIds[i]]) {
                restsArray.push(attributes[this.restIds[i]].split(','))
            }
        }
        return restsArray.length > 0 ? restsArray[0].map((_, colIndex) => restsArray.map(row => row[colIndex])) : [];

    }

    OO.ui.MoleculeRestsWidget.prototype.getRestsAsAttributes = function() {
        let restArray = this.getRestsFromModel();
        let restAttributes = {};
        // transpose array (exchange rows and columns)
        let transposedArray = restArray[0].map((_, colIndex) => restArray.map(row => row[colIndex]));

        for(let i = 0; i < transposedArray.length; i++) {
            restAttributes[this.restIds[i]] = transposedArray[i].join(',');
        }
        return restAttributes;
    }

    OO.ui.MoleculeRestsWidget.prototype.getRestsFromModel = function() {
        let rows = [];
        $('tr.molecule-rest', this.table).each(function(i, row) {
            let rowEl = $(row);
            let columns = [];
            $('textarea[aria-hidden!="true"]', rowEl).each(function(j, textarea) {
                columns.push($(textarea).val());
            });
            rows.push(columns);
        });
        return rows;
    }
}( OO ) );