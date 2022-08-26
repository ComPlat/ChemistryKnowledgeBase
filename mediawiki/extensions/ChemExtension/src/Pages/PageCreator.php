<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\MolfileProcessor;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Title;

class PageCreator
{

    /**
     * @throws Exception
     */
    public function createNewMoleculePage(ChemForm $chemForm, ?Title $parent = null): ?Title
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_MASTER
        );

        $chemFormRepository = new ChemFormRepository($dbr);
        $key = $chemForm->getMoleculeKey();
        $id = $chemFormRepository->addChemForm($key);

        $title = self::getPageTitleToCreate($id, $chemForm->getMolOrRxn());

        if ($title->exists()) {
            // TODO: temporarily save always for debugging
            //return $title;
        }

        $pageContent = $this->getPageContent($chemForm, $parent);

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
    private function getPageContent(ChemForm $chemForm, ?Title $parent = null): string
    {
        $pageContent = $this->getTemplate($chemForm, $parent);
        if ($chemForm->hasRGroupDefinitions()) {
            $pageContent .= "\n\n==R-Groups==";
            $pageContent .= "\n" . $this->getRGroupTable($chemForm);
        }
        return $pageContent;
    }

    /**
     * @param ChemForm $chemForm
     * @return string
     */
    private function getTemplate(ChemForm $chemForm, ?Title $parent = null): string
    {

        if ($chemForm->isReaction()) {
            $template = "ChemicalReaction";
        } else {
            $template = "ChemicalFormula";
        }
        $pageContent = "{{" . $template;
        $pageContent .= "\n|moleculeKey={$chemForm->getMoleculeKey()}";
        $pageContent .= "\n|molOrRxn={$chemForm->getMolOrRxn()}";
        $pageContent .= "\n|smiles={$chemForm->getSmiles()}";
        $pageContent .= "\n|inchi={$chemForm->getInchi()}";
        $pageContent .= "\n|inchikey={$chemForm->getInchiKey()}";
        $pageContent .= "\n|width={$chemForm->getWidth()}";
        $pageContent .= "\n|height={$chemForm->getHeight()}";
        $pageContent .= "\n|float={$chemForm->getFloat()}";
        $parentArticle = !is_null($parent) ? $parent->getPrefixedText() : '';
        $pageContent .= "\n|parent={$parentArticle}";
        $pageContent .= "\n}}";
        return $pageContent;
    }

    private function getRGroupTable(ChemForm $chemForm): string
    {
        if (count($chemForm->getRGroups()) === 0) {
            return '';
        }
        return "\n{{#showMoleculeCollection: }}";
    }

    /**
     * @param int $id
     * @param ChemForm $chemForm
     * @return Title|null
     */
    public static function getPageTitleToCreate(int $id, $formula): ?Title
    {
        $idWithBase = $id + ChemFormRepository::BASE_ID;
        if (MolfileProcessor::isReactionFormula($formula)) {
            $title = Title::newFromText("Reaction:Reaction_$idWithBase");
        } else {
            if (MolfileProcessor::hasRGroups($formula)) {
                $title = Title::newFromText("Molecule:Collection_$idWithBase");
            } else {
                $title = Title::newFromText("Molecule:Molecule_$idWithBase");
            }
        }
        return $title;
    }

}