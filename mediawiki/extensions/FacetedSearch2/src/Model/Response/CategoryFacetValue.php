<?php
namespace DIQA\FacetedSearch2\Model\Response;

class CategoryFacetValue
{
    public string $category;
    public string $displayTitle;
    public string $url;

    /**
     * CategoryFacetValue constructor.
     * @param string $namespace
     * @param string $displayTitle
     * @param string $url
     */
    public function __construct(string $namespace, string $displayTitle, string $url)
    {
        $this->category = $namespace;
        $this->displayTitle = $displayTitle;
        $this->url = $url;
    }

}
