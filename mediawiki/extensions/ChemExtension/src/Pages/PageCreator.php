<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\MoleculeRestBuilder\MoleculesImportJob;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use Title;
use Exception;
use JobQueueGroup;

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
        $key = $chemForm->getChemFormId();
        $id = $chemFormRepository->addChemForm($key);
        $chemForm->setDatabaseId($id);

        $idWithBase = $id + ChemFormRepository::BASE_ID;
        if ($chemForm->isReaction()) {
            $title = Title::newFromText("Reaction:Reaction_$idWithBase");
        } else {
            $title = Title::newFromText("Molecule:Molecule_$idWithBase");
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
        if (!is_null($chemForm->getRests()) && count($chemForm->getRests()) > 0) {
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
        return "\n{{#showMoleculeCollection: }}";
    }

}