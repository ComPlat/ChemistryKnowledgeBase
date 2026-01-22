<?php

namespace DIQA\ChemExtension\Experiments;

use Exception;

class Legacy {
    public static function checkLegacyExperiments($templateParam): string
    {
        $legacyProperties = [
            'TON CO' => 'Turnover_number__CO',
            'TON CH4' => 'Turnover_number__CH4',
            'TON H2' => 'Turnover_number__H2',
            'TON HCOOH' => 'Turnover_number__HCOOH',
            'TON MeOH' => 'Turnover_number__MeOH',

            'TOF CO' => 'Turnover_frequency__CO',
            'TOF CH4' => 'Turnover_frequency__CH4',
            'TOF H2' => 'Turnover_frequency__H2',
            'TOF HCOOH' => 'Turnover_frequency__HCOOH',
            'TOF MeOH' => 'Turnover_frequency__MeOH',

            'Φ CO' => 'Quantum_yield__CO',
            'Φ CH4' => 'Quantum_yield__CH4',
            'Φ H2' => 'Quantum_yield__H2',
            'Φ HCOOH' => 'Quantum_yield__HCOOH',
            'Φ MeOH' => 'Quantum_yield__MeOH',

        ];

        if (array_key_exists($templateParam, $legacyProperties)) {
            return $legacyProperties[$templateParam];
        }
        throw new Exception("$templateParam has wrong syntax");
    }
}
