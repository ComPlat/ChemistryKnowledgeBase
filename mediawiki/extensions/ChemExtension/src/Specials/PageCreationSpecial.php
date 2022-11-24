<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Utils\WikiTools;
use OOUI\Tag;
use Philo\Blade\Blade;
use SpecialPage;
use Title;
use Exception;

class PageCreationSpecial extends SpecialPage
{

    private $blade;

    protected function __construct($title)
    {
        parent::__construct($title);
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ( $views, $cache );
    }

    protected function getHelpSection($title): Tag
    {
        $helpButton = new Tag('a');
        $helpPage = Title::newFromText($title);
        $helpButton->setAttributes(['href' => $helpPage->getFullURL(), 'target' => '_blank']);
        $helpButton->appendContent("Show help");
        $helpSection = new Tag('div');
        $helpSection->appendContent($helpButton);
        $helpSection->addClasses(['chemext-help-link']);
        return $helpSection;
    }

    protected function createPageAndRedirect(Title $topicTitle, string $superTopics)
    {

            global $wgScriptPath;
            $superTopicsAsWikiText = array_map(function($topic) { return "[[Category:$topic]]";}, explode("\n", $superTopics));
            $pageContent = implode("\n", $superTopicsAsWikiText);

            if ($topicTitle->exists()) {
                throw new Exception("Page creation failed because page already exists");
            }
            $successful = WikiTools::doEditContent($topicTitle, $pageContent, "auto-generated", EDIT_NEW);
            if ($successful) {
                header("Location: $wgScriptPath/index.php/{$topicTitle->getPrefixedDBKey()}?veaction=edit");
            } else {
                throw new Exception("Page creation failed. Try again");
            }

    }

    protected function getPresetDataForTitleInput($paramValue) {
        if ($paramValue == '') {
            return [];
        }
        return explode("\n", $paramValue);
    }

    protected function showErrorHint($message) {
        return $this->blade->view()->make("error", ['message' => $message])->render();
    }
}