<?php
namespace DIQA\FacetedSearch2\Model\Response;

use DIQA\FacetedSearch2\Model\Common\MWTitle;

class MWTitleWithURL extends MWTitle {

    public string $url;


    public function __construct(string $title, string $displayTitle, string $url)
    {
        parent::__construct($title, $displayTitle);
        $this->url = $url;

    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }



}