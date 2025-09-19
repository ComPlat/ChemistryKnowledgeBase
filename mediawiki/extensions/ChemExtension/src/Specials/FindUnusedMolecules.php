<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use MediaWiki\MediaWikiServices;
use eftec\bladeone\BladeOne;
use SpecialPage;
use Exception;

class FindUnusedMolecules extends SpecialPage
{
    private $blade;
    private static $AVAILABLE_LIMITS = [ 20, 50, 100, 500 ];

    public function __construct()
    {
        parent::__construct('FindUnusedMolecules');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new BladeOne ($views, $cache);

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

        $output->addWikiTextAsContent("This page shows molecules/reactions which are not used in the wiki.");
        $this->addPagination($output, $offset, $limit);

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new ChemFormRepository($dbr);
        $ids = $repo->getUnusedMoleculeIds($limit, $offset);

        $moleculeTitles = array_map(function($e) {
            $title = \Title::newFromText($e, NS_MOLECULE);
            if (!$title->exists()) {
                $title = \Title::newFromText($e, NS_REACTION);
                if (!$title->exists()) {
                    $title = "Molecule entry without according page: {$e}";
                }
            }
            return $title;
        }, $ids);
        $output->addHTML($this->blade->run("findUnusedMolecules.results", [

            'moleculeTitles' => $moleculeTitles,
            'startIndex' => $offset
        ])
            );

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
        $output->addHTML($this->blade->run("findUnusedMolecules.pagination", [
            'wgScriptPath' => $wgScriptPath,
            'limits' => self::$AVAILABLE_LIMITS,
            'offset' => $offset,
            'limit' => $limit
        ])
            );
    }

}