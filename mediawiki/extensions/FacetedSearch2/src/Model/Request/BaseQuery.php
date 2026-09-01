<?php

namespace DIQA\FacetedSearch2\Model\Request;

use DIQA\FacetedSearch2\ConfigTools;

abstract class BaseQuery {

    public string $searchText = '';
    /* @var string[] */
    public $categoryFacets = [];
    /* @var int[] */
    public $namespaceFacets = [];

    /**
     * @var \DIQA\FacetedSearch2\Model\Request\PropertyFacet[]
     */
    public  $propertyFacets = [];

    /**
     * @return string
     */
    public function getSearchText(): string
    {
        return $this->searchText;
    }

    /**
     * @return array
     */
    public function getCategoryFacets(): array
    {
        return $this->categoryFacets;
    }

    /**
     * @return array
     */
    public function getNamespaceFacets(): array
    {
        return $this->namespaceFacets;
    }

    /**
     * @return PropertyFacet[]
     */
    public function getPropertyFacets(): array
    {
        return $this->propertyFacets;
    }

    /**
     * @param string $searchText
     * @return BaseQuery
     */
    public function setSearchText(string $searchText): BaseQuery
    {
        $this->searchText = $searchText;
        return $this;
    }

    /**
     * @param array $categoryFacets
     * @return BaseQuery
     */
    public function setCategoryFacets(array $categoryFacets): BaseQuery
    {
        $this->categoryFacets = $categoryFacets;
        return $this;
    }

    /**
     * @param array $namespaceFacets
     * @return BaseQuery
     */
    public function setNamespaceFacets(array $namespaceFacets): BaseQuery
    {
        $this->namespaceFacets = $namespaceFacets;
        return $this;
    }

    /**
     * @param PropertyFacet[] $propertyFacets
     * @return BaseQuery
     */
    public function setPropertyFacets(array $propertyFacets): BaseQuery
    {
        $this->propertyFacets = $propertyFacets;
        return $this;
    }

    public function updateQuery(BaseQuery $query): BaseQuery {
        $this->searchText = $query->getSearchText();
        $this->propertyFacets = $query->getPropertyFacets();
        $this->categoryFacets = $query->getCategoryFacets();
        $this->namespaceFacets = $query->getNamespaceFacets();
        return $this;
    }

    public function applyMandatoryFilters(): BaseQuery
    {
        $allowedNamespaces = ConfigTools::getAllowedNamespaces();
        $intersectedNamespaces = array_intersect($allowedNamespaces, $this->getNamespaceFacets());
        $namespaceFacets = empty($this->getNamespaceFacets()) ? $allowedNamespaces : $intersectedNamespaces;
        $this->setNamespaceFacets($namespaceFacets);
        return $this;
    }
}
