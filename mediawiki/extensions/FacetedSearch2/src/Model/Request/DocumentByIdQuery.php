<?php

namespace DIQA\FacetedSearch2\Model\Request;

class DocumentByIdQuery {

    public string $id;

    public static function fromJson($json): DocumentByIdQuery
    {
        $mapper = new \JsonMapper();
        return $mapper->map(json_decode($json), new DocumentByIdQuery());
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}