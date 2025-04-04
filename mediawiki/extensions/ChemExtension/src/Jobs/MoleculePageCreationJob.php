<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientMock;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageComparer;
use DIQA\ChemExtension\PubChem\PubChemService;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\HtmlTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use DIQA\ChemExtension\Utils\WikiTools;
use Job;
use MediaWiki\MediaWikiServices;
use Title;
use Exception;

class MoleculePageCreationJob extends Job
{
    private $logger;
    private $chemForm;
    private $parent;
    private $rGroupClient;
    private $publicationPage;

    public function __construct($title, $params)
    {
        parent::__construct('MoleculePageCreationJob', $title, $params);
        $this->logger = new LoggerUtils('MoleculePageCreationJob', 'ChemExtension');
        $this->chemForm = $params['chemForm'];
        $this->parent = $params['parent'];
        $this->publicationPage = $params['publicationPage'];

        global $wgCEUseMoleculeRGroupsClientMock;
        $this->rGroupClient = $wgCEUseMoleculeRGroupsClientMock ? new MoleculeRGroupServiceClientMock()
            : new MoleculeRGroupServiceClientImpl();
    }

    public function run()
    {
        $pageContent = $this->getPageContent();
        $title = parent::getTitle();
        if ($title->exists()) {
            $templateComparer = new MoleculePageComparer(WikiTools::getText($title), $pageContent);
            $pageContent = $templateComparer->getUpdatedContent();
        }

        $successful = WikiTools::doEditContent($title, $pageContent, "auto-generated",
            $title->exists() ? EDIT_UPDATE : EDIT_NEW);
        if (!$successful) {
            throw new Exception("Could not create/update molecule/reaction page");
        }
        $this->renderMoleculeIfNecessary();

        $object = $this->chemForm->hasRGroupDefinitions() ? "molecule collection" : "molecule";
        $message = "Created/updated $object page: {$title->getPrefixedText()}, smiles: {$this->chemForm->getSmiles()}";
        $this->logger->log($message);

        if ($this->chemForm->hasRGroupDefinitions()) {
            $jobParams = [];
            $jobParams['moleculeCollections'] = [ ['title' => $title, 'chemForm' => $this->chemForm] ];
            $job = new RGroupMaterializationJob($this->publicationPage, $jobParams);
            $job->run();

        }
        \Hooks::run('CleanupChemExtState');
    }

    /**
     * @return string
     */
    private function getPageContent(): string
    {
        return $this->getTemplate();
    }

    /**
     * @return string
     */
    private function getTemplate(): string
    {
        if ($this->chemForm->isReaction()) {
            $template = "ChemicalReaction";
        } else if ($this->chemForm->hasRGroupDefinitions()) {
            $template = "MoleculeCollection";
        } else {
            $template = "Molecule";
        }

        $pubChemTemplateData = $this->getSanitizedMoleculeData();
        $formulaTemplateData = $this->getFormulaTemplateData();

        return $this->serializeTemplate($template, array_merge($pubChemTemplateData, $formulaTemplateData));
    }

    private function getRawPubChemData($inchiKey): ?array
    {
        try {
            if (is_null($inchiKey)) return null;
            $service = new PubChemService();
            $result = $service->getPubChem($inchiKey);
            $record = $result['record'];
            $synonyms = $result['synonyms'];
            $categories = $result['categories'];

            return [
                'cid' => $record->getCID(),
                'iupacName' => $record->getIUPACName(),
                'molecularMass' => $record->getMolecularMass(),
                'molecularFormula' => $record->getMolecularFormula(),
                'logP' => $record->getLogP(),
                'synonyms' => array_slice($synonyms->getSynonyms(), 0, min(10, count($synonyms->getSynonyms()))),
                'cas' => $synonyms->getCAS(),
                'hasVendors' => $categories->hasVendors(),

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
            $title = Title::newFromText("Reaction:$id");
        } else {
            $title = Title::newFromText("Molecule:$id");
        }
        return $title;
    }

    /**
     * @return string
     */
    private function getSanitizedMoleculeData(): array
    {
        $pubChemData = $this->getRawPubChemData($this->chemForm->getInchiKey());
        if (is_null($pubChemData)) {
            return $this->getChemscannerMoleculeData($this->chemForm->getMolOrRxn());
        }

        // sanitize and format data
        $firstSynonym = reset($pubChemData['synonyms']);
        $firstSynonym = $firstSynonym === false ? '' : self::sanitize($firstSynonym);
        $pubChemData['trivialname'] = $firstSynonym;
        $pubChemData['abbrev'] = '';
        $pubChemData['molecularFormula'] = HtmlTools::formatSumFormula($pubChemData['molecularFormula']);
        $pubChemData['synonyms'] = implode('$', array_map(function ($e) {
            return self::sanitize($e);
        }, $pubChemData['synonyms']));
        $pubChemData['iupacName'] = self::sanitize($pubChemData['iupacName']);
        $pubChemData['hasVendors'] = $pubChemData['hasVendors'] ? 'true' : 'false';

        return $pubChemData;
    }

    private static function sanitize($s)
    {
        $s = str_replace(']', ')', $s);
        $s = str_replace('[', '(', $s);
        $s = str_replace('}', ')', $s);
        $s = str_replace('{', '(', $s);
        return $s;
    }

    /**
     * @param ChemForm $chemForm
     * @param Title|null $parent
     * @return string
     */
    private function getFormulaTemplateData(): array
    {


        $formulaTemplateData = [];
        $formulaTemplateData['moleculeKey'] = $this->chemForm->getMoleculeKey();
        $formulaTemplateData['molOrRxn'] = $this->chemForm->getMolOrRxn();
        $formulaTemplateData['smiles'] = $this->chemForm->getSmiles();
        $formulaTemplateData['inchi'] = $this->chemForm->getInchi();
        $formulaTemplateData['inchikey'] = $this->chemForm->getMoleculeKey();
        $formulaTemplateData['width'] = $this->chemForm->getWidth();
        $formulaTemplateData['height'] = $this->chemForm->getHeight();
        $formulaTemplateData['float'] = $this->chemForm->getFloat();
        $parentArticle = !is_null($this->parent) ? $this->parent->getPrefixedText() : '';
        $formulaTemplateData['parent'] = $parentArticle;

        return $formulaTemplateData;
    }

    private function serializeTemplate(string $template, array $data): string
    {
        $text = "{{" . $template;
        foreach ($data as $key => $value) {
            $text .= "\n|$key=$value";
        }
        $text .= "\n}}";
        return $text;
    }

    /**
     * @param $molfile
     * @return array
     */
    private function getChemscannerMoleculeData($molfile): array
    {
        try {
            $pubChemData = [];
            $pubChemData['trivialname'] = '';
            $pubChemData['abbrev'] = '';
            $pubChemData['molecularFormula'] = '';
            $pubChemData['molecularMass'] = '';
            $pubChemData['synonyms'] = '';
            $pubChemData['hasVendors'] = '';
            $metadata = $this->rGroupClient->getMetadata($molfile);
            $pubChemData['molecularFormula'] = HtmlTools::formatSumFormula($metadata['molecularFormula']);
            $pubChemData['molecularMass'] = $metadata['molecularMass'];
        } catch (Exception $e) {
            $this->logger->debug('Requesting molecule metadata from Chemscanner failed: ' . $e->getMessage());
        }
        return $pubChemData;
    }

    private function renderMoleculeIfNecessary()
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY );

        $chemFormRepo = new ChemFormRepository($dbr);
        $img = $chemFormRepo->getChemFormImageByKey($this->chemForm->getMoleculeKey());
        if (is_null($img) || $img == '' || ChemTools::isEmptySVGImage(base64_decode($img))) {
            try {
                $client = new MoleculeRendererClientImpl();
                $renderedMolecule = $client->render($this->chemForm->getMolOrRxn());
                $chemFormRepo->addOrUpdateChemFormImage($this->chemForm->getMoleculeKey(), base64_encode($renderedMolecule->svg));
            } catch(Exception $e) {
                $this->logger->debug('Failed to render molecule: '.$this->chemForm->getMoleculeKey());
            }
        }
    }

}