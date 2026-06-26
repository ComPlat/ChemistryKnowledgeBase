<?php

namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Utils\WikiTools;

class CategoryFacetCount
{
    public string $category;
    public string $displayTitle;
    public int $count;

    /**
     * CategoryFacetCount constructor.
     * @param string $category
     * @param string $displayTitle
     * @param int $count
     */
    public function __construct(string $category, string $displayTitle, int $count)
    {
        $this->category = $category;
        $this->displayTitle = WikiTools::stripHtml($displayTitle);
        $this->count = $count;
    }

    public static function fromCategory(string $category, int $count): self
    {
        return new CategoryFacetCount($category, WikiTools::getDisplayTitleForCategory($category), $count);
    }

}
