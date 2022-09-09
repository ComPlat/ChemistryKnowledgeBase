<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Parser;
use SMWQueryProcessor;

class VEFormInput {

    public static function renderVEFormInput(Parser $parser, $form)
    {
        if (WikiTools::isInVisualEditor()) {
            $queryResults = QueryUtils::executeBasicQuery('[[Category:Experiment]]');
            $html = '';
            $titleText = [];
            $objects = $queryResults->getResults();
            foreach ($objects as $row) {
                $titleText[] = $row->getTitle()->getText();

            }
            $html = implode($titleText, ',');
        } else {
            $printouts = [QueryUtils::newPropertyPrintRequest('Field1')];
            $parameters = ['format' => 'table'];
            SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

            $processedParams = SMWQueryProcessor::getProcessedParams($parameters, $printouts);
            $smwQueryObject = SMWQueryProcessor::createQuery(
                '[[Category:Experiment]]',
                $processedParams,
                SMWQueryProcessor::SPECIAL_PAGE,
                '',
                $printouts
            );
            $html = SMWQueryProcessor::getResultFromQuery($smwQueryObject, $processedParams, SMW_OUTPUT_HTML, SMWQueryProcessor::INLINE_QUERY);
            //$html = "rendered content";
        }
        return [str_replace("\n", "", $html), 'noparse' => true, 'isHTML' => true];
    }
}
