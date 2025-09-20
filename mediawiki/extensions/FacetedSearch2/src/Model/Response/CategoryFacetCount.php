<?php

namespace DIQA\FacetedSearch2\Model\Response;

class CategoryFacetCount
{
    public string $category;
    public string $displayTitle;
    public int $count;

    /**
     * CategoryFacetCount constructor.
     * @param string $category
     * @param int $count
     */
    public function __construct(string $category, string $displayTitle, int $count)
    {
        $this->category = $category;
        $this->displayTitle = $displayTitle;
        $this->count = $count;
    }

}
