<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\Utils\HtmlTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Title;

class MoleculePageCreator
{

    private $logger;

    public function __construct()
    {
        $this->logger = new LoggerUtils('PageCreator', 'ChemExtension');
    }

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
        if (count($chemForm->getRGroups()) > 0) {
            $this->logger->log("Created molecule collection page: {$title->getPrefixedText()}, smiles: {$chemForm->getSmiles()}");
        } else {
            $this->logger->log("Created molecule/reaction page: {$title->getPrefixedText()}, smiles: {$chemForm->getSmiles()}");
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

        $pubChemTemplate = $this->getPubChemTemplate($chemForm);
        $formulaTemplate = $this->getFormulaTemplate($chemForm, $parent);

        return $pubChemTemplate . "\n\n" . $formulaTemplate;
    }

    private function getRGroupTable(ChemForm $chemForm): string
    {
        if (count($chemForm->getRGroups()) === 0) {
            return '';
        }
        return "\n{{#showMoleculeCollection: }}";
    }

    private function getPubChemData($inchiKey) {
        try {
            if (is_null($inchiKey)) return null;
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
            $repo = new PubChemRepository($dbr);
            $result = $repo->getPubChemResult($inchiKey);
            $record = $result['record'];
            $synonyms = $result['synonyms'];
            $categories = $result['categories'];
            return [
                'cid' => $record->getCID(),
                'iupac_name' => $record->getIUPACName(),
                'molecular_mass' => $record->getMolecularMass(),
                'molecular_formula' => $record->getMolecularFormula(),
                'log_p' => $record->getLogP(),
                'synonyms' => array_slice($synonyms->getSynonyms(), 0, min(10, count($synonyms->getSynonyms()))),
                'cas' => $synonyms->getCAS(),
                'has_vendors' => $categories->hasVendors(),

            ];

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    /**
     * @param int $id
     * @param ChemForm $chemForm
     * @return Title|null
     */
    public static function getPageTitleToCreate(int $id, $formula): ?Title
    {
        if (MolfileProcessor::isReactionFormula($formula)) {
            $title = Title::newFromText("Reaction:Reaction_$id");
        } else {
            if (MolfileProcessor::hasRGroups($formula)) {
                $title = Title::newFromText("Molecule:Collection_$id");
            } else {
                $title = Title::newFromText("Molecule:Molecule_$id");
            }
        }
        return $title;
    }

    /**
     * @param ChemForm $chemForm
     * @return string
     */
    private function getPubChemTemplate(ChemForm $chemForm): string
    {
        $pubChemTemplate = '';
        $pubChemData = $this->getPubChemData($chemForm->getInchiKey());
        if (!is_null($pubChemData)) {
            $pubChemTemplate .= "{{PubChem";
            $pubChemTemplate .= "\n|cid={$pubChemData['cid']}";
            $pubChemTemplate .= "\n|cas={$pubChemData['cas']}";
            $pubChemTemplate .= "\n|iupacName={$pubChemData['iupac_name']}";
            $firstSynonym = reset($pubChemData['synonyms']);
            $firstSynonym = $firstSynonym === false ? '' : self::sanitize($firstSynonym);
            $pubChemTemplate .= "\n|trivialname={$firstSynonym}";
            $pubChemTemplate .= "\n|abbrev=";
            $pubChemTemplate .= "\n|molecularMass={$pubChemData['molecular_mass']}";
            $formula = HtmlTools::formatSumFormula($pubChemData['molecular_formula']);
            $pubChemTemplate .= "\n|molecularFormula={$formula}";
            $pubChemTemplate .= "\n|logP={$pubChemData['log_p']}";
            $synonyms = implode(',', array_map(function ($e) {
                return self::sanitize($e);
            }, $pubChemData['synonyms']));
            $pubChemTemplate .= "\n|synonyms={$synonyms}";
            $hasVendors = $pubChemData['has_vendors'] ? 'true' : 'false';
            $pubChemTemplate .= "\n|hasVendors={$hasVendors}";
            $pubChemTemplate .= "\n}}";
        }
        return $pubChemTemplate;
    }

    private static function sanitize($s) {
        return str_replace([',', '[', ']'], '', $s);
    }

    /**
     * @param ChemForm $chemForm
     * @param Title|null $parent
     * @return string
     */
    private function getFormulaTemplate(ChemForm $chemForm, ?Title $parent): string
    {
        if ($chemForm->isReaction()) {
            $template = "ChemicalReaction";
        } else if ($chemForm->hasRGroupDefinitions()) {
            $template = "MoleculeCollection";
        } else {
            $template = "ChemicalFormula";
        }
        $formulaTemplate = "{{" . $template;
        $formulaTemplate .= "\n|moleculeKey={$chemForm->getMoleculeKey()}";
        $formulaTemplate .= "\n|molOrRxn={$chemForm->getMolOrRxn()}";
        $formulaTemplate .= "\n|smiles={$chemForm->getSmiles()}";
        $formulaTemplate .= "\n|inchi={$chemForm->getInchi()}";
        $formulaTemplate .= "\n|inchikey={$chemForm->getInchiKey()}";
        $formulaTemplate .= "\n|width={$chemForm->getWidth()}";
        $formulaTemplate .= "\n|height={$chemForm->getHeight()}";
        $formulaTemplate .= "\n|float={$chemForm->getFloat()}";
        $parentArticle = !is_null($parent) ? $parent->getPrefixedText() : '';
        $formulaTemplate .= "\n|parent={$parentArticle}";
        $formulaTemplate .= "\n}}";
        return $formulaTemplate;
    }

}