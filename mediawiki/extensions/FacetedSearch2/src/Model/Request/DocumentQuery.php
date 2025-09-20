<?php

namespace DIQA\FacetedSearch2\Model\Request;

class DocumentQuery extends BaseQuery {

    /**
     * @var \DIQA\FacetedSearch2\Model\Common\Property[]
     */
    public $extraProperties = [];

    /**
     * @var Sort[]
     */
    public $sorts = [];

    public ?int $limit = 10;
    public ?int $offset = 0;

    public static function fromJson($json): DocumentQuery
    {
        $mapper = new \JsonMapper();
        return $mapper->map(json_decode($json), new DocumentQuery());
    }

    /**
     * @return \DIQA\FacetedSearch2\Model\Common\Property[]
     */
    public function getExtraProperties(): array
    {
        return $this->extraProperties;
    }

    /**
     * @return Sort[]
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @param \DIQA\FacetedSearch2\Model\Common\Property[] $extraProperties
     * @return DocumentQuery
     */
    public function setExtraProperties(array $extraProperties): DocumentQuery
    {
        $this->extraProperties = $extraProperties;
        return $this;
    }

    /**
     * @param Sort[] $sorts
     * @return DocumentQuery
     */
    public function setSorts(array $sorts): DocumentQuery
    {
        $this->sorts = $sorts;
        return $this;
    }

    /**
     * @param int|null $limit
     * @return DocumentQuery
     */
    public function setLimit(?int $limit): DocumentQuery
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int|null $offset
     * @return DocumentQuery
     */
    public function setOffset(?int $offset): DocumentQuery
    {
        $this->offset = $offset;
        return $this;
    }


}