<?php

namespace DIQA\FacetedSearch2;

interface FacetedSearchUpdateClient
{
    /**
     * Creates/Updates a wiki page (=Document) in the index.
     *
     * @param array $docs array of Document objects
     * @return void
     */
    public function updateDocuments(... $docs): void;

    /**
     * Deletes a wiki page with the given page-ID from the index
     * @param string $id
     * @return void
     */
    public function deleteDocument(string $id): void;

    /**
     * Clears ALL documents from the index.
     *
     * @return void
     */
    public function clearAllDocuments(): void;

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

    /**
     * Refreshes the index (i.e. makes query results consistent with recent updates)
     * @return void
     */
    public function refreshIndex(): void;

    /**
     * True if multiple update requests can be sent as one network request
     * @return bool
     */
    public function supportBulkUpdates(): bool;

}