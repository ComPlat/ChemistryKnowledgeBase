<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Widgets\MoleculeSearchWidget;
use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\Tag;
use OutputPage;
use Philo\Blade\Blade;
use SpecialPage;

class ModifyMolecule extends SpecialPage
{
    private $blade;

    public function __construct()
    {
        parent::__construct('ModifyMolecule', 'delete');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

    }

    public function doesWrites() {
        return true;
    }

    function execute($par)
    {
        parent::execute($par);
        global $wgScriptPath;
        $output = $this->getOutput();
        $this->setHeaders();
        OutputPage::setupOOUI();

        try {
            $form = $this->createGUI($wgScriptPath);

            $output->addHTML($this->blade->view()->make("modifyMolecule.page", [

            ])
                ->render());
            $output->addHTML($form);
        } catch(Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

    private function getMolfileFromInChIKey() {

        $inchikey = $this->getRequest()->getText('inchikey', '');
        if ($inchikey === '') {
            return NULL;
        }
        $molfile = NULL;
        $queryResults = QueryUtils::executeBasicQuery("[[InChIKey::$inchikey]]", [QueryUtils::newPropertyPrintRequest("Molfile")]);
        if ($row = $queryResults->getNext()) {
            $molfile = $this->getMolfile($row, $inchikey);
        }
        if (is_null($molfile)) {
            throw new Exception("Cannot find molecule with ID: $inchikey");
        }
        return $molfile;
    }

    /**
     * @param string $wgScriptPath
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createGUI(string $wgScriptPath): FormLayout
    {
        $inchikey = $this->getRequest()->getText('inchikey', '');
        $inchikeyInput = new FieldLayout(
            new MoleculeSearchWidget([
                'id' => 'inchikey',
                'infusable' => true,
                'name' => 'inchikey',
                'value' => $inchikey,
                'placeholder' => 'Enter Molecule ID, InChIKey, abbreviation or synonym'
            ]),
            [
                'align' => 'top',
                'label' => 'Molecule ID, InChIKey, abbreviation or synonym'
            ]
        );

        $loadButton = new ButtonInputWidget([
            'id' => 'load-molecule',
            'type' => 'submit',
            'label' => 'Load molecule',
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);

        $modifyButton = new ButtonInputWidget([
            'id' => 'modify-molecule',
            'type' => 'button',
            'label' => 'Modify molecule',
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA );
        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormId = $chemFormRepo->getChemFormId($inchikey);
        $moleculePage = \Title::newFromText($chemFormId, NS_MOLECULE);
        $modifyButton->setDisabled(is_null($moleculePage) || !$moleculePage->exists());

        $random = uniqid();
        $ketcherURL = "$wgScriptPath/extensions/ChemExtension/ketcher/index-editor.html?random=$random";
        $iframe = new Tag('iframe');
        $iframe->setAttributes([
            'src' => $ketcherURL,
            'id' => 'mp-ketcher-editor',
            'formula' => $this->getMolfileFromInChIKey(),
            'chemformid' => $chemFormId,
            ]);

        $form = new FormLayout(['items' => [$inchikeyInput, $loadButton, $iframe, $modifyButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }

    /**
     * @param $row
     * @param string $inchikey
     * @return Smiles string
     * @throws Exception
     */
    private function getMolfile($row, string $inchikey)
    {
        $smiles = NULL;
        reset($row);
        $column = next($row);
        $dataItem = $column->getNextDataItem();
        if ($dataItem === false) {
            throw new Exception("Cannot find Molefile annotation for molecule with ID: $inchikey");
        }
        if ($dataItem->getDIType() == \SMWDataItem::TYPE_BLOB) {
            $smiles = $dataItem->getString();
        }
        return $smiles;
    }


}