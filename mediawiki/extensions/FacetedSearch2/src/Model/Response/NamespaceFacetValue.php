<?php
namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Utils\WikiTools;

class NamespaceFacetValue
{
    public int $namespace;
    public string $displayTitle;

    /**
     * NamespaceFacetValue constructor.
     * @param int $namespace
     * @param string $displayTitle
     * @param string $url
     */
    public function __construct(int $namespace, string $displayTitle)
    {
        $this->namespace = $namespace;
        $this->displayTitle = WikiTools::stripHtml($displayTitle);
    }

}
