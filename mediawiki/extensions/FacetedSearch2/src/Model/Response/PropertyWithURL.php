<?php

namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Model\Common\Property;

class PropertyWithURL extends Property
{
    public string $displayTitle;
    public string $url;

    public function __construct(string $title, string $displayTitle, int $type, string $url)
    {
        parent::__construct($title, $type);
        $this->displayTitle = $displayTitle;
        $this->url = $url;
    }

}
