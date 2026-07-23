<?php

namespace DIQA\FacetedSearch2;

use DIQA\FacetedSearch2\Model\Update\Document;

interface FacetedSearchDependantUpdates {

    /**
     * Updates a wiki page (=Document) in the index. It also updates all documents that have a relation to
     * the given document.
     * @param Document $doc
     * @return void
     */
    public function updateDocumentWithDependant(Document $doc): void;

}