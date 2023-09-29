<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentListRenderer;
use DIQA\ChemExtension\Experiments\ExperimentRenderer;
use DIQA\ChemExtension\Utils\WikiTools;
use DIQA\InvestigationImport\Importer\ImportFileReader;
use MediaWiki\MediaWikiServices;
use Parser;
use Exception;
use Philo\Blade\Blade;
use Title;
use LocalFile;

class ExperimentList
{

    /**
     * Renders a list of experiments. Lets the user edit the experiments in VE mode.
     *
     * @param Parser $parser
     *
     * @return array
     * @throws Exception
     */
    public static function renderExperimentList(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            if (!isset($parameters['form']) || !isset($parameters['name'])) {
                throw new Exception("required parameters: 'name' and 'form'");
            }

            $title = WikiTools::getCurrentTitle($parser);
            if (is_null($title)) {
                throw new Exception("could not identify current title");
            }
            $importFile = $parameters['importFile'] ?? '';
            if ($importFile != '') {
                self::importInvestigationFile($importFile, $title, $parameters['name'], $parameters['form']);
            }
            $renderer = new ExperimentListRenderer([
                'page' => $title,
                'form' => $parameters['form'],
                'name' => $parameters['name'],
                'index' => null
            ]);
            $html = $renderer->render();
            return [WikiTools::sanitizeHTML($html), 'noparse' => true, 'isHTML' => true];

        } catch (Exception $e) {
            $html = self::getBlade()->view()->make("error", ['message' => $e->getMessage()])->render();
            return [$html, 'noparse' => true, 'isHTML' => true];
        }
    }

    /**
     * @throws Exception
     */
    private static function getBlade(): Blade
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        return new Blade ( $views, $cache );
    }

    private static function importInvestigationFile($importFilePageName, Title $title, $investigationName, $investigationType)
    {
        $investigationTitle = Title::newFromText($title->getText() . "/" . $investigationName);
        if ($investigationTitle->exists()) {
           return;
        }
        $fileTitle = Title::newFromText($importFilePageName, NS_FILE);
        if (!$fileTitle->exists()) {
            throw new Exception("'$importFilePageName' does not exist.");
        }
        $localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
        $file = LocalFile::newFromTitle($fileTitle, $localRepo);
        self::importData($investigationTitle, $file->getLocalRefPath(), $investigationType);
    }

    /**
     * Imports the investigation data from a file.
     *
     * @param Title $investigationTitle the investigation page being created
     * @param string $fullPath absolute path of file to import
     * @param string $investigationType eg. Cyclic_Voltammetry_experiments or Photocatalytic_CO2_conversion_experiments
     */
    private static function importData(Title $investigationTitle, string $fullPath, string $investigationType)
    {
        $reader = new ImportFileReader();
        $experiment_array = $reader->open_zip_extr_data($fullPath);
        $wikitext = implode("\n", $experiment_array);
        WikiTools::doEditContent($investigationTitle, $wikitext, "auto-generated",EDIT_NEW);
    }
}
