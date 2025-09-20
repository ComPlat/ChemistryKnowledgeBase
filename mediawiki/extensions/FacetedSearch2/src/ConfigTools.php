<?php

namespace DIQA\FacetedSearch2;

use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\SolrClient\SolrRequestClient;
use DIQA\FacetedSearch2\SolrClient\SolrUpdateClient;
use DIQA\FacetedSearch2\Utils\ArrayTools;
use DIQA\FacetedSearch2\Utils\WikiTools;
use SMW\DataTypeRegistry;
use SMWDataItem;
use SMWDIProperty;

class ConfigTools {

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
        //FIXME: store in MW-object cache
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
                    $result[] = new Property($property, Datatype::STRING);
                    break;
                case SMWDataItem::TYPE_WIKIPAGE:
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
        global $fsgBackendUpdateClient;
        if (!isset($fsgBackendUpdateClient)) {
            $fsgBackendUpdateClient = SolrUpdateClient::class;
        }
        return new $fsgBackendUpdateClient;
    }

    public static function getFacetedSearchClient(): FacetedSearchClient
    {
        global $fsgBackendQueryClient;
        if (!isset($fsgBackendQueryClient)) {
            $fsgBackendQueryClient = SolrRequestClient::class;
        }
        return new $fsgBackendQueryClient;
    }
}
