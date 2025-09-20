<?php
namespace DIQA\FacetedSearch2\Model\Response;

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
        $this->displayTitle = $displayTitle;
        $this->count = $count;
    }


}
