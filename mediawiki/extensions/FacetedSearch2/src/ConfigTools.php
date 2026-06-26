<?php

namespace DIQA\FacetedSearch2;

use DIQA\FacetedSearch2\ElasticSearch\ElasticSearchQueryClient;
use DIQA\FacetedSearch2\ElasticSearch\ElasticSearchUpdateClient;
use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\SolrClient\SolrRequestClient;
use DIQA\FacetedSearch2\SolrClient\SolrUpdateClient;
use DIQA\FacetedSearch2\Utils\ArrayTools;
use DIQA\FacetedSearch2\Utils\WikiTools;
use SMW\DataTypeRegistry;
use SMWDataItem;
use SMW\DIProperty as SMWDIProperty;

class ConfigTools
{

    public static function initializeServersideConfig(): void
    {
        global $fs2gAnnotationsInSnippet, $fs2gPromotionProperty,
               $fs2gDemotionProperty, $fs2gShowFileInOverlay,
               $fs2gExtraPropertiesToRequest;
        $extraProperties = self::getPropertiesForAnnotations($fs2gAnnotationsInSnippet);
        if ($fs2gPromotionProperty !== false) {
            $extraProperties[] = new Property($fs2gPromotionProperty, Datatype::BOOLEAN);
        }
        if ($fs2gDemotionProperty !== false) {
            $extraProperties[] = new Property($fs2gDemotionProperty, Datatype::BOOLEAN);
        }
        if ($fs2gShowFileInOverlay !== false) {
            $extraProperties[] = new Property("Diqa import fullpath", Datatype::STRING);
        }
        $fs2gExtraPropertiesToRequest = $extraProperties;

        global $fs2gNamespacesToShow;
        $allowedNamespaces = ConfigTools::getAllowedNamespaces();
        $fs2gNamespacesToShow = array_intersect($fs2gNamespacesToShow, $allowedNamespaces);

        global $fs2gFacetControlOrder;
        if (count($fs2gFacetControlOrder) === 0) {
            $fs2gFacetControlOrder = ["selectedFacetLabel", "selectedFacetView", "selectedCategoryView", "removeAllFacets", "divider",
                "facetView", "categoryDropDown", "categoryView", "categoryTree"];
        }
        global $fs2gHeaderControlOrder;
        if (count($fs2gHeaderControlOrder) === 0) {
            $fs2gHeaderControlOrder = ["sortView", "searchView", "saveSearchLink", "createArticleLink"];
        }
    }

    public static function getAllowedNamespaces(): array
    {
        global $fs2gNamespaceConstraint, $fs2gNamespacesToShow;
        if (!isset($fs2gNamespaceConstraint) || count($fs2gNamespaceConstraint) === 0) {
            return $fs2gNamespacesToShow;
        }

        $userGroups = WikiTools::getUserGroups();

        $allowedNamespaces = [];
        foreach ($fs2gNamespaceConstraint as $group => $namespaces) {
            if (in_array($group, $userGroups)) {
                foreach ($namespaces as $namespace) {
                    $allowedNamespaces[] = $namespace;
                }
            }
        }
        return array_unique($allowedNamespaces);
    }

    public static function getPropertiesForAnnotations($fs2gAnnotationsInSnippet): array
    {

        $result = [];
        $allExtraProperties = ArrayTools::flatten(array_values($fs2gAnnotationsInSnippet));
        foreach ($allExtraProperties as $property) {
            $smwProperty = SMWDIProperty::newFromUserLabel($property);
            $typeId = $smwProperty->findPropertyValueType();
            $type = DataTypeRegistry::getInstance()->getDataItemByType($typeId);

            // The property names of all attributes are built based on their type.
            switch ($type) {
                case SMWDataItem::TYPE_BOOLEAN:
                    $result[] = new Property($property, Datatype::BOOLEAN);
                    break;
                case SMWDataItem::TYPE_NUMBER:
                    $result[] = new Property($property, Datatype::NUMBER);
                    break;
                case SMWDataItem::TYPE_BLOB:
                default:
                    $result[] = new Property($property, Datatype::STRING);
                    break;
                case SMWDataItem::TYPE_WIKIPAGE:
                case SMWDataItem::TYPE_NOTYPE:
                    $result[] = new Property($property, Datatype::WIKIPAGE);
                    break;
                case SMWDataItem::TYPE_TIME:
                    $result[] = new Property($property, Datatype::DATETIME);
                    break;
            }
        }
        return $result;
    }

    public static function getFacetedSearchUpdateClient(): FacetedSearchUpdateClient
    {
        global $fs2gBackend;
        switch ($fs2gBackend) {
            case 'solr':
            default:
                $backendUpdateClientClass = SolrUpdateClient::class;
                break;
            case 'elastic':
                $backendUpdateClientClass = ElasticSearchUpdateClient::class;
                break;

        }
        return new $backendUpdateClientClass;
    }

    public static function getFacetedSearchClient(): FacetedSearchClient
    {
        global $fs2gBackend;
        switch ($fs2gBackend) {
            case 'solr':
            default:
                $backendQueryClientClass = SolrRequestClient::class;
                break;
            case 'elastic':
                $backendQueryClientClass = ElasticSearchQueryClient::class;
                break;
        }
        return new $backendQueryClientClass;
    }
}
