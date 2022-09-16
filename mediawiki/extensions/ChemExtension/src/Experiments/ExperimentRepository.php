<?php

namespace DIQA\ChemExtension\Experiments;

use Exception;

class ExperimentRepository {

    private $experiments;
    private static $INSTANCE;

    public function __construct()
    {
        $this->experiments = [new Experiment1(), new Experiment2()];
    }

    public static function getInstance(): ExperimentRepository
    {
        if (is_null(self::$INSTANCE)) {
            self::$INSTANCE = new ExperimentRepository();
        }
        return self::$INSTANCE;
    }

    /**
     * @param $template
     * @return Experiment
     * @throws Exception
     */
    public function getExperiment($template): Experiment
    {
        $experiments = array_filter($this->experiments, function($e) use ($template) { return $e->getTemplate() === $template; });
        if (count($experiments) === 0) {
            throw new Exception("Experiment does not exist: $template");
        }
        return reset($experiments);
    }


}