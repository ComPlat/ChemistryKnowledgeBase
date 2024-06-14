<?php

use DIQA\ChemExtension\Utils\WikiTools;
use DIQA\ChemExtension\WikiRepository;
use DIQA\Formatter\Formatter;
use DIQA\ChemExtension\Utils\TemplateEditor;
use MediaWiki\MediaWikiServices;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\PubChem\PubChemRepository;

require_once __DIR__ . '/PageIterator.php';

/**
 * Repairs molecule pages
 */
class repairMoleculePages extends PageIterator
{
    private Formatter $formatter;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Repairs molecule pages');

    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }

    protected function processPage(Title $title)
    {
        if ($title->getNamespace() !== NS_MOLECULE) {
            return;
        }

        $text = WikiTools::getText($title);

        $te = new TemplateEditor($text);


        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $repo = new ChemFormRepository($dbr);
        $moleculeKey = $repo->getMoleculeKey($title->getText());
        $pubChem = new PubChemRepository($dbr);
        $pubChemData = $pubChem->getPubChemResult($moleculeKey);
        if (is_null($pubChemData)) {
            return;
        }
        $iupacName = $pubChemData['record']->getIUPACName();
        $synonyms = $pubChemData['synonyms']->getSynonyms();
        $synonyms = array_slice($synonyms, 0, min(10, count($synonyms)));
        $synonyms = array_map(fn($e) => str_replace(['$'], '', $e), $synonyms);
        $synonyms = array_map(fn($e) => str_replace('[', '(', $e), $synonyms);
        $synonyms = array_map(fn($e) => str_replace(']', ')', $e), $synonyms);
        $te->replaceTemplateParameters('Molecule', [
            'iupacName' => $iupacName,
            'synonyms' => implode('$', $synonyms),
            'trivialname' => $synonyms[0] ?? '' ]);
        $text = $te->getWikiText();

        WikiTools::doEditContent($title, $text, "auto-updated", EDIT_UPDATE);
        print "\n".$title->getPrefixedText();
    }

    protected function init()
    {
        // TODO: Implement init() method.
    }
}

$maintClass = repairMoleculePages::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
