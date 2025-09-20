<?php

namespace DIQA\FacetedSearch2\Model\Response;

class Document
{
    public string $id;
    /* @var PropertyFacetValues[] */
    public array $propertyFacets;
    /* @var CategoryFacetValue[] */
    public array $categoryFacets;
    /* @var CategoryFacetValue[] */
    public array $directCategoryFacets;
    /* @var NamespaceFacetValue[] */
    public $namespaceFacet;

    public string $title;
    public string $displayTitle;
    public string $url;
    public int $score;
    public ?string $highlighting;

    /**
     * Document constructor.
     * @param string $id
     * @param PropertyFacetValues[] $propertyFacets
     * @param CategoryFacetValue[] $categoryFacets
     * @param CategoryFacetValue[] $directCategoryFacets
     * @param NamespaceFacetValue $namespaceFacet
     * @param string $title
     * @param string $displayTitle
     * @param string $url
     * @param int $score
     * @param string|null $highlighting
     */
    public function __construct(string $id, array $propertyFacets, array $categoryFacets, array $directCategoryFacets, NamespaceFacetValue $namespaceFacet, string $title, string $displayTitle, string $url, int $score, ?string $highlighting)
    {
        $this->id = $id;
        $this->propertyFacets = $propertyFacets;
        $this->categoryFacets = $categoryFacets;
        $this->directCategoryFacets = $directCategoryFacets;
        $this->namespaceFacet = $namespaceFacet;
        $this->title = $title;
        $this->displayTitle = $displayTitle;
        $this->url = $url;
        $this->score = $score;
        $this->highlighting = $highlighting;
    }


}
