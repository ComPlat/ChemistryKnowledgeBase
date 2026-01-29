<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\CategoryIndexRepository;
use DIQA\ChemExtension\Experiments\ExperimentEditor;
use DIQA\ChemExtension\Experiments\Legacy;
use DIQA\ChemExtension\Jobs\PublicationTaggingJob;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\IMaintainableDatabase;


/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Repairs the photo cat inv
 */
class repairPhotoCatInv extends \Maintenance
{



    public function __construct()
    {
        parent::__construct();
        $this->addOption('dryrun', 'Does not actually create jobs, just show the list of publications');
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
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

        $pages = $this->retrieveTemplates("Photocatalytic_CO2_conversion_experiments");

        /*$pages[] = Title::newFromText("Photocatalytic_CO2_conversion", NS_TEMPLATE);
        $pages[] = Title::newFromText("Photocatalytic_CO2_conversion_experiments", NS_TEMPLATE);
        $pages[] = Title::newFromText("Photocatalytic_CO2_conversion_experiments", PF_NS_FORM);*/
        foreach($pages as $page) {
            $text = WikiTools::getText($page);
            foreach($legacyProperties as $old => $new) {
                $text = str_replace($old, $new, $text);
            }
            print "\n".$page->getPrefixedText();
            if (!$this->hasOption('dryrun')) {
                WikiTools::doEditContent($page, $text, "cleanup template params", EDIT_UPDATE);
            }
        }
      echo "\n";
    }


    private function retrieveTemplates($templateName): array
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $res = $db->newSelectQueryBuilder()
            ->select('pub.page_title AS title, pub.page_namespace AS namespace')
            ->from('templatelinks')
            ->join('linktarget', 'lt', ['lt.lt_id = tl_target_id'])
            ->join('page', 'pub', ['pub.page_id = tl_from'])
            ->where(["lt.lt_title" => $templateName])
            ->caller(__METHOD__)
            ->fetchResultSet();

        $pages = [];
        if ($res->numRows() > 0) {
            while ($row = $res->fetchObject()) {
                $pages[] = Title::newFromText($row->title, $row->namespace);

            }
        }
        $res->free();

        return $pages;
    }


}

$maintClass = repairPhotoCatInv::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
