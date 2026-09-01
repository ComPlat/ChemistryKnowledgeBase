<?php

namespace DIQA\FacetedSearch2\Model\Response;

class DocumentsResponse
{
    use DebugInfo;

    public int $numResults;
    /* @var Document[] */
    public array $docs;
    /* @var CategoryFacetCount[] */
    public array $categoryFacetCounts;
    /* @var PropertyFacetCount[] */
    public array $propertyFacetCounts;
    /* @var NamespaceFacetCount[] */
    public array $namespaceFacetCounts;

    public ?bool $titleExists;

    /**
     * SolrDocumentsResponse constructor.
     * @param int $numResults
     * @param Document[] $docs
     * @param CategoryFacetCount[] $categoryFacetCounts
     * @param PropertyFacetCount[] $propertyFacetCounts
     * @param NamespaceFacetCount[] $namespaceFacetCounts
     * @param bool $titleExists (null means: the search text is no valid title)
     */
    public function __construct(int $numResults, array $docs, array $categoryFacetCounts, array $propertyFacetCounts, array $namespaceFacetCounts,
    ?bool $titleExists)
    {
        $this->numResults = $numResults;
        $this->docs = $docs;
        $this->categoryFacetCounts = $categoryFacetCounts;
        $this->propertyFacetCounts = $propertyFacetCounts;
        $this->namespaceFacetCounts = $namespaceFacetCounts;
        $this->titleExists = $titleExists;
    }

    public function titleExists(): ?bool
    {
        return $this->titleExists;
    }

    /**
     * @return int
     */
    public function getNumResults(): int
    {
        return $this->numResults;
    }

    /**
     * @return Document[]
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    /**
     * @return CategoryFacetCount[]
     */
    public function getCategoryFacetCounts(): array
    {
        return $this->categoryFacetCounts;
    }

    /**
     * @return PropertyFacetCount[]
     */
    public function getPropertyFacetCounts(): array
    {
        return $this->propertyFacetCounts;
    }

    /**
     * @return NamespaceFacetCount[]
     */
    public function getNamespaceFacetCounts(): array
    {
        return $this->namespaceFacetCounts;
    }

}
