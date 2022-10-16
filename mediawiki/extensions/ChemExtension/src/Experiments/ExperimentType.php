<?php

namespace DIQA\ChemExtension\Experiments;

class ExperimentType
{

    private $type;
    private $label;
    private $tabs;

    /**
     * ExperimentType constructor.
     * @param $experimentType array data from config
     */
    public function __construct(array $experimentType)
    {
        $this->type = $experimentType['type'] ?? null;
        $this->label = $experimentType['label'] ?? null;
        $this->tabs = $experimentType['tabs'] ?? null;
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
     * @return array|mixed
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    public function getTab($index)
    {
        return $this->tabs[$index];
    }



    public function hasOnlyOneTab(): bool
    {
        return $this->tabs === null;
    }
}