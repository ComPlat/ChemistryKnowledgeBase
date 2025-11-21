<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\Jobs\PublicationTaggingJob;
use DIQA\ChemExtension\Utils\QueryUtils;


/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Auto-tags all publications in the Publication category
 */
class autoTagPublications extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Adds tags to all publications in the Publication category');
        $this->addOption('contains', 'Only process publications that contain this string');
        $this->addOption('dryrun', 'Does not actually create jobs, just show the list of publications');
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        $results = QueryUtils::executeBasicQuery("[[Category:Publication]]");
        $publications = [];
        while ($row = $results->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem === false) continue;
            if (str_starts_with($dataItem->getTitle()->getText(), "Test")) continue;
            $publications[] = $dataItem->getTitle();
        }
        foreach($publications as $publication) {
            if ($this->hasOption('contains')
                && !str_contains($publication->getPrefixedText(), $this->getOption('contains'))) {
                continue;
            }
            echo "\nProcessing publication: " . $publication->getPrefixedText() . "...";
            if (!$this->hasOption("dryrun")) {
                $job = new PublicationTaggingJob($publication);
                $job->run();
                echo "created job...";
            }
            echo "done";
        }
        echo "\n";
    }



}

$maintClass = autoTagPublications::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
