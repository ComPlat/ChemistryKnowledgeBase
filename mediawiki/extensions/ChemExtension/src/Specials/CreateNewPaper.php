<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\Utils\ArrayTools;
use Exception;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\HtmlSnippet;
use OOUI\Tag;
use OOUI\TextInputWidget;
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
        global $wgScriptPath;

        try {

            $output = $this->getOutput();
            $this->setHeaders();

            global $wgRequest;
            $doi = $wgRequest->getText('doi', '');
            if ($doi != '') {
                try {
                    $data = RenderLiterature::resolveDOI($doi);
                    $title = ArrayTools::getFirstIfArray($data->title);
                    $title = str_replace(" ", '_', $title);
                    header("Location: $wgScriptPath/index.php/$title?veaction=edit");
                } catch (Exception $e) {
                    $this->getOutput()->addHTML($e->getMessage());
                    return;
                }
            }

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

        $text = <<<TEXT
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore 
magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, 
no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam 
nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et  
justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. 
TEXT;

        $infoText = new Tag('p');
        $infoText->appendContent(new HtmlSnippet($text));

        $createPaperButton = new ButtonInputWidget([
            'classes' => ['chemext-createpaper-button'],
            'id' => 'chemext-create-paper',
            'type' => 'submit',
            'label' => $this->msg('chemext-create-paper')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);

        $doiInput = new FieldLayout(
            new TextInputWidget(['id' => 'chemext-doi', 'name' => 'doi', 'placeholder' => $this->msg('doi-hint')]),
            [
                'align' => 'top',
                'label' => $this->msg('doi-label')->text()
            ]
        );

        $form = new FormLayout(['items' => [$infoText, $doiInput, $createPaperButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:CreateNewPaper",
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }


}
