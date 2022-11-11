<?php

namespace DIQA\ChemExtension\Specials;

use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\SelectFileInputWidget;
use OutputPage;
use Philo\Blade\Blade;
use SpecialPage;

class CreateNewPaper extends SpecialPage
{

    private $blade;

    function __construct()
    {
        parent::__construct('CreateNewPaper');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new Blade ($views, $cache);

    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        try {

            $output = $this->getOutput();
            $this->setHeaders();

            OutputPage::setupOOUI();

            $form = $this->createGUI();
            $output->addHTML($form);

        } catch (\Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

    /**
     * @param string $wgScriptPath
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createGUI(): FormLayout
    {
        global $wgScriptPath;

        $form = new FormLayout(['items' => [],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:CreateNewPaper",
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }


}
