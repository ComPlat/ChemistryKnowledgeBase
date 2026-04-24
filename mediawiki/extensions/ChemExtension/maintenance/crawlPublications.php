<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\PublicationSearch\CrossRefAPI;
use DIQA\ChemExtension\PublicationSearch\PublicationFetcher;
use DIQA\ChemExtension\PublicationSearch\PublicationSearchRepository;
use DIQA\ChemExtension\PublicationSearch\PublicationSearchResult;
use DIQA\ChemExtension\Jobs\CrossRefSearchJob;
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
 * Crawls new publications and classifies them
 */
class crawlPublications extends \Maintenance
{

    private PublicationSearchRepository $publicationRepo;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Crawls new publications (default 1 day old)');
        $this->addOption('dryrun', 'Does not actually create jobs, just show the list of publications');
        $this->addOption('days', 'Considers last x days (default: 1)');
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        $days = $this->getOption('days', 1);
        print "\nCrawling new publications from $days days ago...";
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->publicationRepo = new PublicationSearchRepository($dbr);

        $apis = PublicationFetcher::factory();
        foreach($apis as $api) {
            print "\nUsing API " . $api->name() . "...";
            $api->fetchPublication(function($publications) {
                global $wgCrossRefJournalList;
                $wgCrossRefJournalList = $wgCrossRefJournalList ?? [];
                if (count($wgCrossRefJournalList) > 0) {
                    $publications = array_filter($publications, fn (PublicationSearchResult $e)
                    => $e->getJournal() === '' || in_array($e->getJournal(), $wgCrossRefJournalList));
                }
                $this->addPublications($publications);
                }, $days);
        }

        print "\nAdding jobs...";
        $unclassifiedDois = $this->publicationRepo->getUnclassifiedDois();
        foreach ($unclassifiedDois as $doi) {
            $this->addJob($doi);
        }
        print "\nDone.";
        echo "\n";
    }



    private function addPublications(array $results): void
    {
        foreach ($results as $result) {
            $publication = $this->publicationRepo->findByDoi($result->getDoi());
            if (is_null($publication)) {
                $this->publicationRepo->addPublication($result);
                print "\nAdded publication: " . $result->getDoi();
            } else {
                print "\nPublication already exists: " . $result->getDoi();
            }

        }
    }


    public function addJob(string $doi): void
    {
        if ($this->hasOption("dryrun")) {
            return;
        }

        if ($this->publicationRepo->doesJobExistsForDoi($doi)) {
            print "\nJob already exists for publication with DOI: " . $doi;
            return;
        }
        $title = str_replace("/", "-", $doi);
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $jobQueue->push(new CrossRefSearchJob($title, ['doi' => $doi]));
        print "\nCreated AI-job for publication with DOI: " . $doi;

    }


}

$maintClass = crawlPublications::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
