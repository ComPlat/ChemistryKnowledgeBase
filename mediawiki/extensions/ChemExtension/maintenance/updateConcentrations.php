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

class updateConcentrations extends \Maintenance
{
    private Formatter $formatter;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Updates catalyst concentrations in experiments (multiply by 1.000)');

    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }


    public function execute()
    {
        echo "\nUpdates catalyst concentrations in experiments (multiply by 1.000)...";
        $query = "[[Category:Investigation]]";
        $results = QueryUtils::executeBasicQuery($query,
            [], ['limit' => 10000]);


        while ($row = $results->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem === false) continue;

            $investigation = $dataItem->getTitle();
            $text = \DIQA\ChemExtension\Utils\WikiTools::getText($investigation);

            $newText = preg_replace_callback("/\\|cat conc=(.*)/", function (array $matches) {
                $conc = $matches [1];
                return "|cat conc=" . ($conc * 1000);
             }, $text);

            if ($newText !== $text) {
                print "\nUpdated: ". $investigation->getPrefixedText();
                \DIQA\ChemExtension\Utils\WikiTools::doEditContent($investigation, $newText, "auto-updated by updateConcentrations", EDIT_UPDATE);
            } else {
                print "\nSkipped: ". $investigation->getPrefixedText();
            }

        }

        echo "\n";
    }
}

$maintClass = updateConcentrations::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
