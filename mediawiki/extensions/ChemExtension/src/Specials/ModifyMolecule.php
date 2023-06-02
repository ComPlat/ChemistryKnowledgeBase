<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\Tag;
use OOUI\TextInputWidget;
use Philo\Blade\Blade;
use SpecialPage;
use Exception;
use OutputPage;

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

    private function getMolfileFromChemFormId() {

        $chemFormId = $this->getRequest()->getText('chemformid', '');
        if ($chemFormId === '') {
            return NULL;
        }
        $queryResults = QueryUtils::executeBasicQuery("[[Molecule:$chemFormId]]", [QueryUtils::newPropertyPrintRequest("Molfile")]);
        $smiles = NULL;
        if ($row = $queryResults->getNext()) {
            reset($row);
            $column = next($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem === false) {
                throw new Exception("Cannot find Molefile annotation for molecule with ID: $chemFormId");
            }
            if ($dataItem->getDIType() == \SMWDataItem::TYPE_BLOB) {
                $smiles = $dataItem->getString();
            }
        }
        if (is_null($smiles)) {
            throw new Exception("Cannot find molecule with ID: $chemFormId");
        }
        return $smiles;
    }

    /**
     * @param string $wgScriptPath
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createGUI(string $wgScriptPath): FormLayout
    {
        $chemFormId = $this->getRequest()->getText('chemformid', '');
        $chemFormIdInput = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemformid',
                'infusable' => true,
                'name' => 'chemformid',
                'value' => $chemFormId,
                'placeholder' => 'Enter Molecule ID...'
            ]),
            [
                'align' => 'top',
                'label' => 'Molecule-ID'
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
        $moleculePage = \Title::newFromText($this->getRequest()->getText('chemformid', ''), NS_MOLECULE);
        $modifyButton->setDisabled(is_null($moleculePage) || !$moleculePage->exists());

        $random = uniqid();
        $ketcherURL = "$wgScriptPath/extensions/ChemExtension/ketcher/index-editor.html?random=$random";
        $iframe = new Tag('iframe');
        $iframe->setAttributes([
            'src' => $ketcherURL,
            'id' => 'mp-ketcher-editor',
            'formula' => $this->getMolfileFromChemFormId(),
            'chemformid' => $chemFormId,
            ]);

        $form = new FormLayout(['items' => [$chemFormIdInput, $loadButton, $iframe, $modifyButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }


}