<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\ChemScanner\ChemScannerRequest;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\PubChem\PubChemRecordResult;
use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\PubChem\PubChemClient;
use DIQA\ChemExtension\WikiRepository;
use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;
use ExtensionRegistry;
use Exception;
use MediaWiki\MediaWikiServices;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Deletes unused molecule pages and cleans up the index.
 */
class cleanUpMolecules extends \Maintenance
{
    private Formatter $formatter;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Cleans up the unused molecules');
        $this->addOption('dry-run', 'Shows what would happen but does not really delete anything', false, false);
    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }

    private function initFormatter(): void
    {
        $config = new Config([80, 20], [Config::LEFT_ALIGN, Config::LEFT_ALIGN]);
        $config->highlightWord("[DELETED]", Color::fromColor(COLOR::BLACK, Color::GREEN), 1);
        $config->highlightWord("[FAILED]", Color::fromColor(COLOR::BLACK, Color::RED), 1);
        $config->setLeftColumnPadding(0,3);
        $this->formatter = new Formatter($config);
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        try {
            print "\nRemoving unused molecules...";
            $this->initFormatter();
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
            $repo = new ChemFormRepository($dbr);
            $ids = $repo->getUnusedMoleculeIds();
            $services = MediaWikiServices::getInstance();
            foreach ($ids as $id) {
                $moleculeTitle = \Title::newFromText($id, NS_MOLECULE);
                $deleteOk = false;

                if (!$this->hasOption('dry-run') && $moleculeTitle->exists()) {
                    $deleter = $services->getUserFactory()->newFromName("WikiSysop");
                    $page = $services->getWikiPageFactory()->newFromTitle($moleculeTitle);
                    $deletePage = $services->getDeletePageFactory()->newDeletePage($page, $deleter);
                    $deleteStatus = $deletePage
                        ->deleteIfAllowed("cleanUpChemTables");
                    $deleteOk = $deleteStatus->isOK();
                }
                if ($deleteOk && $moleculeTitle->exists()) {
                    echo "\n";
                    echo $this->formatter->formatLine($moleculeTitle->getPrefixedText(), '[DELETED]');
                } else if ($this->hasOption('dry-run') && $moleculeTitle->exists()) {
                    echo "\n";
                    echo $this->formatter->formatLine($moleculeTitle->getPrefixedText(), '[TO BE DELETED]');
                } else if (!$deleteOk && !$this->hasOption('dry-run')) {
                    echo "\n";
                    echo $this->formatter->formatLine($moleculeTitle->getPrefixedText(), '[FAILED]');
                }

            }

            // this is only relevant if there are orphaned entries in the index. Normally the removal of pages
            // should also delete the entries in the index
            $ids = $repo->getUnusedMoleculeIds();
            foreach ($ids as $id) {
                if (!$this->hasOption('dry-run')) {
                    $repo->deleteAllChemFormIndexByChemFormId($id);
                    $repo->deleteChemForm($id);
                }
                echo "\n";
                if ($this->hasOption('dry-run')) {
                    echo $this->formatter->formatLine("Index with ID $id found", '[TO BE DELETED]');
                } else {
                    echo $this->formatter->formatLine("Index with ID $id found", '[DELETED]');
                }
            }
            print "\nfinished\n";
        } catch (Exception $e) {
            $msg = $e->getMessage();
            print "\n$msg\n";
        }
    }

}

$maintClass = cleanUpMolecules::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
