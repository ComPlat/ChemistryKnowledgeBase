<?php

namespace DIQA\FacetedSearch2\Model\Common;

class Property {

    public string $title;
    public int $type;

    /**
     * Property constructor.
     * @param string $title
     * @param int $type
     */
    public function __construct(string $title,
                                int $type)
    {
        $this->title = $title;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

}