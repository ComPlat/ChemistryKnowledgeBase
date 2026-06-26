<?php

namespace DIQA\FacetedSearch2;

interface FacetedSearchUpdateClient
{
    /**
     * Creates/Updates a wiki page (=Document) in the index.
     *
     * @param array $docs array of Document objects
     * @return mixed result ignored for now
     */
    public function updateDocuments(... $docs);

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

    /**
     * Initializes the index.
     */
    public function initIndex(): bool;

    /**
     * Deletes the index.
     * @return void
     */
    public function deleteIndex(): void;

    /**
     * Checks if the index exists.
     * @return bool
     */
    public function existsIndex(): bool;

    public function refreshIndex(): void;

}