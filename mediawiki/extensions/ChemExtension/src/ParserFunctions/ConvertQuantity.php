<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Utils\GeneralTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use Exception;
use Parser;
use SMW\Services\ServicesFactory;
use SMWDIProperty;

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
            if (!isset($parameters['property']) || $value === '') {
                return [GeneralTools::roundNumber($value), 'noparse' => true, 'isHTML' => false];
            }

            $unit = QueryUtils::getUnitForProperty($parameters['property']);
            if (is_null($unit)) {
                return [GeneralTools::roundNumber($value), 'noparse' => true, 'isHTML' => false];
            }

            $propertyDI = SMWDIProperty::newFromUserLabel($parameters['property']);
            $num = new \SMWQuantityValue(\SMWQuantityValue::TYPE_ID);
            $applicationFactory = ServicesFactory::getInstance();

            $num->setDataValueServiceFactory($applicationFactory->create( 'DataValueServiceFactory' ));
            $num->setProperty($propertyDI);
            $num->setUserValue(GeneralTools::roundNumber($value));

            return [$num->getConvertedUnitValues()[$unit->getString()], 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }


}
