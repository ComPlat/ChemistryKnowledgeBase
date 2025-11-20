<?php

namespace DIQA\FacetedSearch2\Update;

use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\MWTitle;
use DIQA\FacetedSearch2\Model\Common\Property;
use DIQA\FacetedSearch2\Model\Update\PropertyValues;
use MediaWiki\Title\Title;
use SMW\DIProperty as SMWDIProperty;
use SMW\DIWikiPage as SMWDIWikiPage;
use SMW\PropertyRegistry;
use SMWDataItem;

class SMWReader {

    public function retrievePropertyValues($title, array &$doc): void
    {
        if (!defined('SMW_VERSION')) {
            return;
        }

        global $fs2gIndexPredefinedProperties;

        $store = smwfGetStore();
        $propertyValuesToAdd = [];

        $subject = SMWDIWikiPage::newFromTitle($title);
        $properties = $store->getProperties($subject);

        foreach ($properties as $property) {
            // skip instance-of and subclass properties
            if ($property->getKey() == "_INST" || $property->getKey() == "_SUBC") {
                continue;
            }

            // check if particular pre-defined property should be indexed
            $preDefinedProperty = PropertyRegistry::getInstance()->getPropertyValueTypeById($property->getKey());
            if (!empty($preDefinedProperty)) {
                // This is a predefined property
                if (isset($fs2gIndexPredefinedProperties) && $fs2gIndexPredefinedProperties === false) {
                    continue;
                }
            }

            if ($this->shouldBeIgnored($property->getDiWikiPage()->getTitle())) {
                continue;
            }

            // retrieve all annotations and index them
            $values = $store->getPropertyValues($subject, $property);

            foreach ($values as $value) {
                if ($value->getDIType() == SMWDataItem::TYPE_WIKIPAGE) {

                    if ($value->getSubobjectName() != "") {

                        global $fs2gIndexSubobjects;
                        if ($fs2gIndexSubobjects !== true) {
                            continue;
                        }

                        // handle record properties
                        if ($value->getSubobjectName() != "") {
                            $subData = smwfGetStore()->getSemanticData($value);
                            $recordProperties = $subData->getProperties();
                            foreach ($recordProperties as $rp) {
                                if (strpos($rp->getKey(), "_") === 0) continue;
                                $propertyValues = $subData->getPropertyValues($rp);
                                $record_value = reset($propertyValues);
                                if ($record_value === false) continue;
                                if ($record_value->getDIType() == SMWDataItem::TYPE_WIKIPAGE) {
                                    $enc_prop = $this->serializeWikiPageDataItem($rp, $record_value);
                                    $propertyValuesToAdd[] = $enc_prop;
                                } else {
                                    $enc_prop = $this->serializeDataItem($rp, $record_value);
                                    if (is_null($enc_prop)) {
                                        continue;
                                    }
                                    $propertyValuesToAdd[] = $enc_prop;
                                }
                            }
                        }
                    } else {
                        // handle relation properties
                        $enc_prop = $this->serializeWikiPageDataItem($property, $value);
                        $propertyValuesToAdd[] = $enc_prop;
                    }

                } else {
                    // handle attribute properties
                    $enc_prop = $this->serializeDataItem($property, $value);
                    if (is_null($enc_prop)) {
                        continue;
                    }
                    $propertyValuesToAdd[] = $enc_prop;
                }
            }
        }

        $doc['smwh_properties'] = $propertyValuesToAdd;
    }

    public function shouldBeIgnored(Title $title): bool
    {
        if (!defined('SMW_VERSION')) {
            return false;
        }
        $store = smwfGetStore();
        $ignoreAsFacetProperty = SMWDIProperty::newFromUserLabel(wfMessage('fs_prop_ignoreasfacet')->text());
        $iafValues = $store->getPropertyValues(SMWDIWikiPage::newFromTitle($title), $ignoreAsFacetProperty);
        return count($iafValues) > 0;
    }


    private function serializeWikiPageDataItem($property, $dataItem): PropertyValues
    {

        $title = $dataItem->getTitle();
        $valueId = $title->getPrefixedText();
        $valueLabel = FacetedSearchUtil::findDisplayTitle($title);
        return new PropertyValues(new Property($property->getLabel(), Datatype::WIKIPAGE),
            [new MWTitle($valueId, $valueLabel)]);
    }


    private function serializeDataItem($property, $dataItem): ?PropertyValues
    {

        $valueXSD = $dataItem->getSerialization();

        $type = $dataItem->getDIType();

        // The values of all attributes are stored according to their type.
        if ($type == SMWDataItem::TYPE_TIME) {

            // Required format: 1995-12-31T23:59:59Z
            $valueXSD = FacetedSearchUtil::getISODateFromDataItem($dataItem);

            return new PropertyValues(new Property($property->getLabel(), Datatype::DATETIME),
                [$valueXSD]);

        } else if ($type == SMWDataItem::TYPE_NUMBER) {
            return new PropertyValues(new Property($property->getLabel(), Datatype::NUMBER),
                [$valueXSD]);

        } else if ($type == SMWDataItem::TYPE_BOOLEAN) {
            return new PropertyValues(new Property($property->getLabel(), Datatype::BOOLEAN),
                [$valueXSD]);

        } else if ($type == SMWDataItem::TYPE_CONCEPT) {
            return null;

        }

        return new PropertyValues(new Property($property->getLabel(), Datatype::STRING),
            [$valueXSD]);


    }

}
