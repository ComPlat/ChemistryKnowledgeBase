<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\WikiTools;
use Title;

class ExperimentType
{

    private $type;
    private $label;
    private $tabs;
    private $properties;
    private $rowTemplate;
    private $mainTemplate;

    /**
     * ExperimentType constructor.
     * @param $experimentType array data from config
     */
    public function __construct(array $experimentType, $mainTemplate)
    {
        $this->mainTemplate = $mainTemplate;
        $this->type = $experimentType['type'] ?? null;
        $this->label = $experimentType['label'] ?? null;
        $this->tabs = $experimentType['tabs'] ?? null;
        $this->properties = $experimentType['properties'] ?? null;
        $this->rowTemplate = $experimentType['rowTemplate'] ?? null;

    }

    public static function fromForm($formName): ExperimentType
    {
        $experimentType = [
            'label' => $formName,
            'type' => 'assay',
            'tabs' => null
        ];
        return new ExperimentType($experimentType);
    }

    /**
     * @return mixed|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return mixed|null
     */
    public function getProperties()
    {
        $templateText = WikiTools::getText(Title::newFromText($this->rowTemplate, NS_TEMPLATE));
        $parser = new TemplateParser($templateText);
        $ast = $parser->parse();
        $node = $ast->getFirstNodeOfType('#subobject');
        if (is_null($node)) {
            return [];
        }
        preg_match_all('/\|([A-z0-9_\s]+)=\{\{\{([A-z0-9_\s]+)\|?\}\}\}/', $node->getTextContent(), $matches);

        $propertyMapping = [];
        for($i = 0; $i < count($matches[0]); $i++) {
            $propertyMapping[$matches[1][$i]] = $matches[2][$i];
        }
        $propertyMapping['BasePageName'] = 'BasePageName';

        return $propertyMapping;
    }

    /**
     * @return mixed|null
     */
    public function getRowTemplate()
    {
        return $this->rowTemplate;
    }

    /**
     * @return mixed
     */
    public function getMainTemplate()
    {
        return $this->mainTemplate;
    }



}