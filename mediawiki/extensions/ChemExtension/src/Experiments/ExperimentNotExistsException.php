<?php

namespace DIQA\ChemExtension\Experiments;

use Exception;

class ExperimentNotExistsException extends Exception {

    const EXPERIMENT_NOT_EXISTS = 1001;
    private $experimentName;

    public function __construct($message, $experimentName)
    {
        parent::__construct($message, self::EXPERIMENT_NOT_EXISTS);
        $this->experimentName = $experimentName;
    }

    /**
     * @return mixed
     */
    public function getExperimentName()
    {
        return $this->experimentName;
    }

}
