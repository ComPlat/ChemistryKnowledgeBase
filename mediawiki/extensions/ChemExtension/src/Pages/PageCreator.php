<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\WikiTools;
use Title;
use Exception;

class PageCreator
{

    /**
     * @throws Exception
     */
    public function createNewMoleculePage(ChemForm $chemForm): ?Title
    {
        $hash = md5($chemForm->getSmiles());
        if ($chemForm->isReaction()) {
            $title = Title::newFromText("Reaction:$hash");
        } else {
            $title = Title::newFromText("Molecule:$hash");
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
        $pageContent .= "\n|width={$chemForm->getWidth()}";
        $pageContent .= "\n|height={$chemForm->getHeight()}";
        $pageContent .= "\n|float={$chemForm->getFloat()}";
        $pageContent .= "\n}}";
        return $pageContent;
    }
}