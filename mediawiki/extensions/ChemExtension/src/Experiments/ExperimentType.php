<?php

namespace DIQA\ChemExtension\Experiments;

class ExperimentType {

    private $type;
    private $label;
    private $baseRowTemplate;
    private $baseHeaderTemplate;
    private $tabs;

    /**
     * ExperimentType constructor.
     * @param $experimentType array data from config
     */
    public function __construct($formName, array $experimentType)
    {
        $this->type = $experimentType['type'] ?? null;
        $this->label = $experimentType['label'] ?? null;
        $this->baseRowTemplate = $experimentType['base-row-template'] ?? null;
        $this->baseHeaderTemplate = $formName;
        $this->tabs = $experimentType['tabs'] ?? [];
    }

    public function getFirstTab() {
        return reset($this->tabs);
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
    public function getBaseRowTemplate()
    {
        return $this->baseRowTemplate;
    }

    /**
     * @return array|mixed
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    public function getTab($index) {
        return $this->tabs[$index];
    }

    /**
     * @return mixed
     */
    public function getBaseHeaderTemplate()
    {
        return $this->baseHeaderTemplate;
    }

    public function hasOnlyOneTab(): bool
    {
        return count($this->tabs) === 1;
    }
}