<?php

namespace DIQA\ChemExtension\Utils;

use ML\IRI\IRI;
use ML\JsonLD\Quad;
use ML\JsonLD\TypedValue;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\Serializer\Serializer;
use SMWExpData as ExpData;

class NQuadProducer extends Serializer
{

    private $quads = [];

    public function getQuads(): array
    {
        return $this->quads;
    }

    public function serializeExpData(ExpData $expData)
    {
        $subExpData = [$expData];
        while (count($subExpData) > 0) {
            $this->serializeNestedExpData(array_pop($subExpData));
        }
    }

    protected function serializeNestedExpData(ExpData $data)
    {
        if (count($data->getProperties()) == 0) {
            return; // nothing to export
        }

        $subject = $data->getSubject();
        foreach ($data->getProperties() as $property) {

            $propertyUri = $property->getUri();
            foreach ($data->getValues($property) as $value) {

                if ($value instanceof ExpLiteral) {
                    $this->quads[] = new Quad(
                        new IRI($subject->getUri()),
                        new IRI($propertyUri),
                        new TypedValue($value->getLexicalForm(), $value->getDatatype())
                    );
                } elseif ($value instanceof ExpResource) {
                    $this->quads[] = new Quad(
                        new IRI($subject->getUri()),
                        new IRI($propertyUri),
                        new IRI($value->getUri())
                    );
                } elseif ($value instanceof ExpData) { // resource (maybe blank node), could have subdescriptions

                    $collection = $value->getCollection();
                    if ($collection !== false) {
                        foreach ($collection as $subvalue) {
                            $this->serializeNestedExpData($subvalue);
                        }
                    } else {
                        if (count($value->getProperties()) > 0) {
                            $this->serializeNestedExpData($value);
                        }
                    }
                }
            }
        }
    }

    protected function serializeNamespace($shortname, $uri)
    {
        // empty
    }

    protected function serializeHeader()
    {
        // empty
    }

    protected function serializeFooter()
    {
        // empty
    }

    public function serializeDeclaration($uri, $typename)
    {
        // empty
    }
}