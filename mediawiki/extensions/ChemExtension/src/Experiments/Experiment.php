<?php

namespace DIQA\ChemExtension\Experiments;

abstract class Experiment {

    public abstract function getTemplate();
    public abstract function getTabs();

    public abstract function getVEModeQuery();

    public function getFirstTab() {
        $tabs = $this->getTabs();
        $keys = array_keys($tabs);
        $first = reset($keys);
        return $tabs[$first];
    }
}
