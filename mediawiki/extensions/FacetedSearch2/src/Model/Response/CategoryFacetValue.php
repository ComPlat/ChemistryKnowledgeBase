<?php
namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Utils\WikiTools;

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
        $this->displayTitle = WikiTools::stripHtml($displayTitle);
        $this->url = $url;
    }

}
