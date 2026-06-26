<?php

namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Utils\WikiTools;

class PropertyWithURL extends Property
{
    public string $displayTitle;
    public string $url;

    public function __construct(string $title, string $displayTitle, int $type, string $url)
    {
        parent::__construct($title, $type);
        $this->displayTitle = WikiTools::stripHtml($displayTitle);
        $this->url = $url;
    }

    public static function fromProperty(Property $property, string $displayTitle, string $url): self
    {
        return new self($property->title, $displayTitle, $property->type, $url);
    }

}
