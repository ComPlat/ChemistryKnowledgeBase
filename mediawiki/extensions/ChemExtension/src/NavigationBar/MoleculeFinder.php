<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\StoreFactory;
use Title;

class MoleculeFinder
{

    private $chemFormRepo;

    /**
     * MoleculeFinder constructor.
     */
    public function __construct()
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $this->chemFormRepo = new ChemFormRepository($dbr);
    }


    public function getMoleculesForPublicationPage(Title $title, $limit, $offset): array
    {
        $chemFormIds = $this->chemFormRepo->getChemFormIdsByPages([$title], $limit, $offset);
        return $this->getMolecules($chemFormIds);

    }

    public function getMoleculesForTopic(Title $title, $limit, $offset): array
    {
        $chemFormIds = $this->chemFormRepo->getChemFormIdsByPages($this->getPagesUnderTopic($title), $limit, $offset);
        return $this->getMolecules($chemFormIds);
    }

    private function getPagesUnderTopic(Title $topic): array
    {

        $queryResults = QueryUtils::executeBasicQuery("[[Category:{$topic->getText()}]] OR [[Subcategory of::{$topic->getText()}]]",
            [], ['limit' => 10000]);
        $results = [];
        while ($row = $queryResults->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $results[$dataItem->getTitle()->getPrefixedText()] = $dataItem->getTitle();
        }
        return $results;
    }

    /**
     * @param array $chemFormIds
     * @return array
     */
    private function getMolecules(array $chemFormIds): array
    {
        $results = [];
        $iupacNameProperty = DIProperty::newFromUserLabel("IUPACName");
        $trivialNameProperty = DIProperty::newFromUserLabel("Trivialname");
        foreach ($chemFormIds as $id) {
            $moleculePage = Title::newFromText($id, NS_MOLECULE);
            $dataItem = StoreFactory::getStore()->getPropertyValues(DIWikiPage::newFromTitle($moleculePage), $iupacNameProperty);
            $iupacName = reset($dataItem);
            if ($iupacName !== false) {
                $results[] = ['page' => $moleculePage, 'name' => $iupacName->getString()];
            } else {
                $dataItem = StoreFactory::getStore()->getPropertyValues(DIWikiPage::newFromTitle($moleculePage), $trivialNameProperty);
                $trivialName = reset($dataItem);
                if ($trivialName !== false) {
                    $results[] = ['page' => $moleculePage, 'name' => $trivialName->getString()];
                } else {
                    $results[] = ['page' => $moleculePage, 'name' => $moleculePage->getText()];
                }
            }
        }
        return $results;
    }
}