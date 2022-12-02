<?php

namespace DIQA\ChemExtension\Experiments;

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
        return $this->properties;
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