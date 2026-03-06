<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\PublicationSearch\CrossRefAPI;
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

        $pageNumber = 0;
        $pageSize = 2;
        $nextCursor = null;
        do {
            print "\nFetching page $pageNumber...";
            $res = $this->fetchPublications($days, $pageSize, $pageNumber, $nextCursor);
            $this->addPublications($res['results']);
            $nextCursor = $res['nextCursor'];
            $pageNumber++;

        } while (count($res['results']) === $pageSize);

        print "Adding jobs...";
        $unclassifiedDois = $this->publicationRepo->getUnclassifiedDois();
        foreach ($unclassifiedDois as $doi) {
            $this->addJob($doi);
        }
        print "\nDone.";
        echo "\n";
    }

    private function fetchPublications(int $daysAgo, int $pageSize, int $pageNumber, $nextCursor = null): array
    {
        // Example: return data from your repository here.
        // Each entry must have keys: title, abstract, doi, date.
        $crossRefApi = new CrossRefAPI();
        $res = $crossRefApi->find('chemistry', $daysAgo,
            ['rows' => $pageSize, 'cursor' => $pageNumber === 0 ? '*' : $nextCursor]
        );

        return ['results' => PublicationSearchResult::fromResult($res), 'nextCursor' => $res->message->{'next-cursor'} ?? null];
    }

    private function addPublications(array $results)
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

        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $jobQueue->push(new CrossRefSearchJob(null, ['doi' => $doi]));
        print "\nCreated AI-job for publication with DOI: " . $doi;

    }


}

$maintClass = crawlPublications::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
