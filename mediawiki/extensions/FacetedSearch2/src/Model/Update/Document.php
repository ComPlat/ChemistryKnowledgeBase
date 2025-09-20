<?php

namespace DIQA\FacetedSearch2\Model\Update;

class Document {

    private string $id;
    private string $title;
    private string $displayTitle;
    private int $namespace;
    private string $fulltext = '';
    private $propertyValues = [];    /* @var PropertyValues[] */
    private $categories = [];        /* @var string[] */
    private $directCategories = [];  /* @var string[] */
    private ?float $boost = null;

    /**
     * Document constructor.
     * @param string $id
     * @param string $title
     * @param string $displayTitle
     * @param number $namespace
     */
    public function __construct(string $id, string $title, string $displayTitle, $namespace)
    {
        $this->id = $id;
        $this->title = $title;
        $this->displayTitle = trim($displayTitle) === '' ? $title: $displayTitle;
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getFulltext(): string
    {
        return $this->fulltext;
    }

    /**
     * @param string $fulltext
     * @return Document
     */
    public function setFulltext(string $fulltext): Document
    {
        $this->fulltext = $fulltext;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPropertyValues()
    {
        return $this->propertyValues;
    }

    /**
     * @param mixed $propertyValues
     * @return Document
     */
    public function setPropertyValues($propertyValues)
    {
        $this->propertyValues = $propertyValues;
        return $this;
    }

    /**
     * @return PropertyValues[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param PropertyValues[] $categories
     * @return Document
     */
    public function setCategories(array $categories): Document
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getDirectCategories(): array
    {
        return $this->directCategories;
    }

    /**
     * @param string[] $directCategories
     * @return Document
     */
    public function setDirectCategories(array $directCategories): Document
    {
        $this->directCategories = $directCategories;
        return $this;
    }

    /**
     * @return number
     */
    public function getBoost(): ?float
    {
        return $this->boost;
    }

    /**
     * @param ?float $boost
     * @return Document
     */
    public function setBoost(?float $boost): Document
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Document
     */
    public function setId(string $id): Document
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Document
     */
    public function setTitle(string $title): Document
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayTitle(): string
    {
        return $this->displayTitle;
    }

    /**
     * @param string $displayTitle
     * @return Document
     */
    public function setDisplayTitle(string $displayTitle): Document
    {
        $this->displayTitle = $displayTitle;
        return $this;
    }

    /**
     * @return int
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param int $namespace
     * @return Document
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }


}