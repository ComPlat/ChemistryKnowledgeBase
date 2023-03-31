<?php

namespace DIQA\ChemExtension\Specials;

use MediaWiki\MediaWikiServices;
use Philo\Blade\Blade;
use SpecialPage;
use Title;

class FindMissingItems extends SpecialPage
{
    private $blade;
    private static $AVAILABLE_LIMITS = [20, 50, 100, 500];

    public function __construct()
    {
        parent::__construct('FindMissingItems');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

    }

    /**
     * @throws \OOUI\Exception
     * @throws \MWException
     */
    function execute($par)
    {

        $output = $this->getOutput();
        $this->setHeaders();
        $limit = $this->getRequest()->getVal("limit", 20);
        $offset = $this->getRequest()->getVal("offset", 0);

        $output->addWikiTextAsContent("This page shows missing items.");
        $this->addPagination($output, $offset, $limit);

        $typesOfItems = [
            'FaultyMolecule',
            'MissingMolecule',
            'CitationNeeded',
            'DOINeeded',
            'MissingInvestigation',
            'MissingSIData',
            'MoreSpecificCategory',
            'MultipleIssues',
            'NoCategory',
            'UnreferencedCategory',
            'WrongMolecule'
        ];

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
        $res = $dbr->select('templatelinks',
            ['tl_from', "GROUP_CONCAT(tl_title SEPARATOR ',') AS types"],
            ["tl_title IN ('".implode("','", $typesOfItems)."')"],
            __METHOD__,
            ['GROUP BY' => 'tl_from']
        );
        foreach ($res as $row) {
            $results[] = [
                'title' => Title::newFromID($row->tl_from),
                'types' => explode(",", $row->types)
            ];
        }

        $output->addHTML($this->blade->view()->make("findMissingItems.results", [

            'results' => $results,
            'startIndex' => $offset
        ])
            ->render());

        $this->addPagination($output, $offset, $limit);
    }

    /**
     * @param \OutputPage $output
     * @param string|null $offset
     * @param string|null $limit
     */
    private function addPagination(\OutputPage $output, ?string $offset, ?string $limit): void
    {
        global $wgScriptPath;
        $output->addHTML($this->blade->view()->make("findUnusedMolecules.pagination", [
            'wgScriptPath' => $wgScriptPath,
            'limits' => self::$AVAILABLE_LIMITS,
            'offset' => $offset,
            'limit' => $limit
        ])
            ->render());
    }

}