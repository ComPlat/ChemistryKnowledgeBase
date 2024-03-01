(function () {
    ve.ce.AnnotateMouseDownHandler = function VeCeAnnotateMouseDownHandler() {
        // Parent constructor - never called because class is fully static
        // ve.ui.AnnotateMouseDownHandler.super.apply( this, arguments );
    };

    /* Inheritance */

    OO.inheritClass(ve.ce.AnnotateMouseDownHandler, ve.ce.KeyDownHandler);

    /* Static properties */

    ve.ce.AnnotateMouseDownHandler.static.name = 'annotateEnter';

    ve.ce.AnnotateMouseDownHandler.static.supportedSelections = ['linear'];

    /* Static methods */

    /**
     * @inheritdoc
     */
    ve.ce.AnnotateMouseDownHandler.static.execute = function (surface, e, node, range, tags, lastText) {

        let view = surface.getView();
        var cursor = range.from,
            documentModel = view.model.getDocument(),
            emptyParagraph = [{type: 'paragraph'}, {type: '/paragraph'}],
            advanceCursor = true,
            nodeModel = null,
            nodeModelRange = null;

        e.preventDefault();

        if (!node.isMultiline()) {
            return true;
        }

        // Handle removal first
        if (!range.isCollapsed()) {
            var txRemove = ve.dm.TransactionBuilder.static.newFromRemoval(documentModel, range);
            range = txRemove.translateRange(range);
            // We do want this to propagate to the surface
            view.model.change(txRemove, new ve.dm.LinearSelection(range));
            // Remove may have changed node at range.from
            node = view.getDocument().getBranchNodeFromOffset(range.from);
        }

        if (node !== null) {
            // Assertion: node is certainly a contentBranchNode
            nodeModel = node.getModel();
            nodeModelRange = nodeModel.getRange();
        }

        let tagsAsStr = tags.join(',');
        let item = ve.ui.DataTransferItem.static.newFromString('{{Annotation|property=Tag|value=' + tagsAsStr + '|display=' + lastText + '}}', 'text/plain');
        const handler = ve.ui.dataTransferHandlerFactory.create('wikitextString', surface, item);
        handler.getInsertableData().done((node) => {
            var txInsert = ve.dm.TransactionBuilder.static.newFromDocumentInsertion(documentModel, range.from, node);

            txInsert.operations[1].insert = [txInsert.operations[1].insert[2], txInsert.operations[1].insert[3]];

            // Commit the transaction
            if (txInsert) {
                view.model.change(txInsert);
                range = txInsert.translateRange(range);
            }

            // Now we can move the cursor forward
            if (advanceCursor) {
                cursor = documentModel.data.getRelativeContentOffset(range.from, 1);
            } else {
                cursor = documentModel.data.getNearestContentOffset(range.from);
            }
            if (cursor === -1) {
                // Cursor couldn't be placed in a nearby content node, so create an empty paragraph
                view.model.change(
                    ve.dm.TransactionBuilder.static.newFromInsertion(
                        documentModel, range.from, emptyParagraph
                    )
                );
                view.model.setLinearSelection(new ve.Range(range.from + 1));
            } else {
                view.model.setLinearSelection(new ve.Range(cursor));
            }
            // Reset and resume polling
            view.surfaceObserver.clear();
            // TODO: This setTimeout appears to be unnecessary (we're not render-locked)
            setTimeout(function () {
                view.findAndExecuteSequences();
            });
        });


        return true;
    };

    /* Registration */

    ve.ce.keyDownHandlerFactory.register(ve.ce.AnnotateMouseDownHandler);
}());