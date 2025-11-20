<?php

namespace DIQA\FacetedSearch2;

use DIQA\FacetedSearch2\Model\Update\Document;

interface FacetedSearchUpdateClient
{
    /**
     * Creates/Updates a wiki page (=Document) in the index.
     *
     * @param array $docs array of Document objects
     * @return mixed result ignored for now
     */
    public function updateDocuments(array $docs);

    /**
     * Deletes a wiki page with the given page-ID from the index
     * @param string $id
     * @return mixed result ignored for now
     */
    public function deleteDocument(string $id);

    /**
     * Clears ALL documents from the index.
     *
     * @return mixed result ignored for now
     */
    public function clearAllDocuments();

}