<?php

namespace DIQA\FacetedSearch2\ElasticSearch;

use DIQA\FacetedSearch2\Model\Common\Datatype;
use DIQA\FacetedSearch2\Model\Common\Property;

class Helper
{

    static function toInternalName(Property $property): string
    {

        switch ($property->getType()) {
            case Datatype::INTERNAL:
                if ($property->getTitle() === 'displaytitle') return '__display';
                if ($property->getTitle() === 'score') return '_score';
                break;
            case Datatype::NUMBER:
                $prefix = "number";
                break;
            case Datatype::DATETIME:
                $prefix = "datetime";
                break;
            case Datatype::BOOLEAN:
                $prefix = "boolean";
                break;
            case Datatype::WIKIPAGE:
                $prefix = "wikipage";
                break;
            case Datatype::STRING:
            default:
                $prefix = "text";
        }
        return "{$prefix}__{$property->getTitle()}";

    }

    static function fromInternalName(string $internalName): Property
    {
        list($type, $name) = explode('__', $internalName);
        $datatype = match ($type) {
            'number' => Datatype::NUMBER,
            'datetime' => Datatype::DATETIME,
            'boolean' => Datatype::BOOLEAN,
            'wikipage' => Datatype::WIKIPAGE,
            'text' => Datatype::STRING,
            default => throw new \InvalidArgumentException(
                sprintf('Unknown datatype prefix: "%s"', $type)
            ),
        };
        return new Property($name, $datatype);
    }

}