<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Utils\GeneralTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use Exception;
use Parser;
use SMW\DIProperty;
use SMW\Services\ServicesFactory;


class ConvertQuantity
{

    /**
     * Converts quantity to display unit
     *
     * @param Parser $parser
     * @return array
     * @throws Exception
     */
    public static function convertQuantity(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            $value = $parameters[''] ?? '';
            $form = $parameters['form'] ?? '';
            if (!isset($parameters['property']) || $value === '') {
                return [GeneralTools::roundNumber($value), 'noparse' => true, 'isHTML' => false];
            }

            $experimentType = ExperimentRepository::getInstance()->getExperimentType($form);
            $unit = $experimentType->getDefaultUnits()[$parameters['property']]
                ?? QueryUtils::getUnitForProperty($parameters['property']);

            if (is_null($unit)) {
                return [GeneralTools::roundNumber($value), 'noparse' => true, 'isHTML' => false];
            }

            $convertedValue = self::convert($parameters['property'], $value, $unit);
            return [$convertedValue, 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }

    public static function convertQuantityByFactor(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            $value = $parameters[''] ?? '';
            $factor = $parameters['factor'] ?? 1.0;
            if ($value === '' || !is_numeric($value)) {
                return [$value, 'noparse' => true, 'isHTML' => false];
            }


            return [$value * $factor, 'noparse' => true, 'isHTML' => false];

        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }

    public static function defaultQuantity(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            if (!isset($parameters['property']) || !isset($parameters['form'])) {
                return ['"property" or "form" parameter missing', 'noparse' => true, 'isHTML' => false];
            }

            $unit = self::getDefaultUnit($parameters['property'], $parameters['form']);

            if (is_null($unit)) {
                return ['-no unit found-', 'noparse' => true, 'isHTML' => false];
            }

            return [$unit, 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }

    public static function getDefaultUnit($property, $form) {
        $experimentType = ExperimentRepository::getInstance()->getExperimentType($form);
        return $experimentType->getDefaultUnits()[$property]
            ?? QueryUtils::getUnitForProperty($property);
    }


    public static function convert($property, $value, $unit) {
        $propertyDI = DIProperty::newFromUserLabel($property);
        $num = new \SMWQuantityValue(\SMWQuantityValue::TYPE_ID);
        $applicationFactory = ServicesFactory::getInstance();

        $num->setDataValueServiceFactory($applicationFactory->create( 'DataValueServiceFactory' ));
        $num->setProperty($propertyDI);
        $num->setUserValue(GeneralTools::roundNumber($value));

        return $num->getConvertedUnitValues()[$unit];
    }
}
