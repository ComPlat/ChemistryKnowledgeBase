<?php
namespace DIQA\FacetedSearch2\Model\Common;

use DIQA\FacetedSearch2\Utils\WikiTools;

class MWTitle {

    public string $title;
    public string $displayTitle;

    /**
     * MWTitle constructor.
     * @param string $title
     * @param string $displayTitle
     */
    public function __construct(string $title, string $displayTitle)
    {
        $this->title = $title;
        $this->displayTitle = WikiTools::stripHtml($displayTitle);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDisplayTitle(): string
    {
        return $this->displayTitle;
    }

}