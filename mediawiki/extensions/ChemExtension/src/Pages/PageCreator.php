<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Title;
use Exception;

class PageCreator
{

    /**
     * @throws Exception
     */
    public function createNewMoleculePage(ChemForm $chemForm): ?Title
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_MASTER
        );

        $chemFormRepository = new ChemFormRepository($dbr);
        if (is_null($chemForm->getInchiKey()) || $chemForm->getInchiKey() === '') {
            $key = $chemForm->getChemFormId();
        } else {
            $key = $chemForm->getInchiKey();
        }
        $id = $chemFormRepository->addChemForm($key);
        $chemForm->setDatabaseId($id);

        if ($chemForm->isReaction()) {
            $title = Title::newFromText("Reaction:Reaction_$id");
        } else {
            $title = Title::newFromText("Molecule:Molecule_$id");
        }
        if ($title->exists()) {
            // TODO: temporarily save always for debugging
            //return $title;
        }

        $pageContent = $this->getPageContent($chemForm);

        $successful = WikiTools::doEditContent($title, $pageContent, "auto-generated",
            $title->exists() ? EDIT_UPDATE : EDIT_NEW);
        if (!$successful) {
            throw new Exception("Could not create molecule/reaction page");
        }

        return $title;
    }

    /**
     * @param ChemForm $chemForm
     * @return string
     */
    private function getPageContent(ChemForm $chemForm): string
    {
        $pageContent = $this->getTemplate($chemForm);
        if (count($chemForm->getRests()) > 0) {
            $pageContent .= "\n\n==Molecule rests==";
            $pageContent .= "\n" . $this->getRestsTable($chemForm);
        }
        return $pageContent;
    }

    /**
     * @param ChemForm $chemForm
     * @return string
     */
    private function getTemplate(ChemForm $chemForm): string
    {
        if ($chemForm->isReaction()) {
            $template = "ChemicalReaction";
        } else {
            $template = "ChemicalFormula";
        }
        $pageContent = "{{" . $template;
        $pageContent .= "\n|databaseId={$chemForm->getDatabaseId()}";
        $pageContent .= "\n|chemFormId={$chemForm->getChemFormId()}";
        $pageContent .= "\n|molOrRxn={$chemForm->getMolOrRxn()}";
        $pageContent .= "\n|smiles={$chemForm->getSmiles()}";
        $pageContent .= "\n|inchi={$chemForm->getInchi()}";
        $pageContent .= "\n|inchikey={$chemForm->getInchiKey()}";
        $pageContent .= "\n|width={$chemForm->getWidth()}";
        $pageContent .= "\n|height={$chemForm->getHeight()}";
        $isReaction = $chemForm->isReaction() ? "true" : "false";
        $pageContent .= "\n|isreaction={$isReaction}";
        $pageContent .= "\n|float={$chemForm->getFloat()}";
        $pageContent .= "\n}}";
        return $pageContent;
    }

    private function getRestsTable($chemForm) {
        if (count($chemForm->getRests()) === 0) {
            return '';
        }
        $restHeaders = array_map(function($e) { return strtoupper($e); }, array_keys($chemForm->getRests()));
        sort($restHeaders);
        $table = <<<WIKITEXT
{| class="wikitable" 
|-
WIKITEXT;
        $table .= "\n! Molecule !! " . implode(" !! ", $restHeaders);
        $table .= "\n|-";
        $rows = self::transpose($chemForm->getRests());
        foreach($rows as $row) {
            ksort($row);
            $table .= "\n| [[Molecule]] || " . implode(" || ", array_values($row));
            $table .= "\n|-";
        }
        $table .= "\n|-";
        $table .= "\n|}";
        return $table;
    }

    private static function transpose($arr) {
        $result = [];
        $keys = array_keys($arr);
        for ($row = 0,  $rows = count(reset($arr)); $row < $rows; $row++) {
            foreach ($keys as $key) {
                $result[$row][$key] = $arr[$key][$row];
            }
        }
        return $result;
    }
}