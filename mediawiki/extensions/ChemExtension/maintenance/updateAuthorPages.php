<?php

use DIQA\ChemExtension\WikiRepository;
use DIQA\Formatter\Formatter;
use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Jobs\CreateAuthorPageJob;

if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

class updateAuthorPages extends \Maintenance
{
    private Formatter $formatter;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Updates author pages');

    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }


    public function execute()
    {
        echo "\nUpdate author pages...";
        $query = "[[-Has subobject::<q>[[Author::+]]</q>]]";
        $results = QueryUtils::executeBasicQuery($query,
            [
                QueryUtils::newPropertyPrintRequest("Author"),
                QueryUtils::newPropertyPrintRequest("Orcid")
            ], ['mainlabel' => '-'] );


        $searchResults = [];
        while ($row = $results->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem === false) continue;

            $author = $dataItem->getString();

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem !== false) {
                $orcid = $dataItem->getString();
            } else {
                $orcid = '-';
            }

            if ($orcid === '-') {
                if (!array_key_exists($author, $searchResults)) {
                    $searchResults[$author] = $orcid;
                }
            } else {
                $searchResults[$author] = $orcid;
            }

        }
        ksort($searchResults);

        foreach($searchResults as $name => $orcid) {

            $job = new CreateAuthorPageJob(null,  ['name' => $name, 'orcid' => $orcid]);
            try {

                $successful = $job->createAuthorPage();
                if ($successful) {
                    echo "\nAdded page: " . $job->getTitle()->getPrefixedText();
                } else {
                    echo "\nSkipped due to error: $name ($orcid)";
                }

            } catch (Exception $e) {
                echo "\nSkipped due to error: $name ($orcid)";
                $this->logger->error($e->getMessage());
            }
        }
        echo "\n";
    }
}

$maintClass = updateAuthorPages::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
