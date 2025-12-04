<?php
namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Utils\WikiTools;

class NamespaceFacetCount
{
    public int $namespace;
    public string $displayTitle;
    public int $count;

    /**
     * NamespaceFacetCount constructor.
     * @param int $namespace
     * @param string $displayTitle
     * @param int $count
     */
    public function __construct(int $namespace, string $displayTitle, int $count)
    {
        $this->namespace = $namespace;
        $this->displayTitle = WikiTools::stripHtml($displayTitle);
        $this->count = $count;
    }


}
