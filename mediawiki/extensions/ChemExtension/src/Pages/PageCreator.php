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
        $chemForm->setId($id);

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
        if ($chemForm->isReaction()) {
            $template = "ChemicalReaction";
        } else {
            $template = "ChemicalFormula";
        }
        $pageContent = "{{".$template;
        $pageContent .= "\n|id={$chemForm->getId()}";
        $pageContent .= "\n|molOrRxn={$chemForm->getMolOrRxn()}";
        $pageContent .= "\n|smiles={$chemForm->getSmiles()}";
        $pageContent .= "\n|inchi={$chemForm->getInchi()}";
        $pageContent .= "\n|inchikey={$chemForm->getInchiKey()}";
        $pageContent .= "\n|width={$chemForm->getWidth()}";
        $pageContent .= "\n|height={$chemForm->getHeight()}";
        $isReaction = $chemForm->isReaction()?"true":"false";
        $pageContent .= "\n|isreaction={$isReaction}";
        $pageContent .= "\n|float={$chemForm->getFloat()}";
        $pageContent .= "\n}}";
        return $pageContent;
    }
}