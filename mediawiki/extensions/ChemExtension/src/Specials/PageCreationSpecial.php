<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Utils\WikiTools;
use OOUI\Tag;
use SpecialPage;
use Title;

class PageCreationSpecial extends SpecialPage
{

    protected function __construct($title)
    {
        parent::__construct($title);

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

    protected function createPageAndRedirect(Title $topicTitle, string $topicSuper)
    {
        try {
            global $wgScriptPath;
            $topicSuper = $topicSuper != '' ? $topicSuper : "Topic";
            $pageContent = "[[Category:$topicSuper]]";

            $successful = WikiTools::doEditContent($topicTitle, $pageContent, "auto-generated",
                $topicTitle->exists() ? EDIT_UPDATE : EDIT_NEW);
            if ($successful) {
                header("Location: $wgScriptPath/index.php/{$topicTitle->getPrefixedDBKey()}?veaction=edit");
            } else {
                throw new Exception("Page creation failed. Try again");
            }
        } catch (Exception $e) {
            $this->getOutput()->addHTML($e->getMessage());
            return;
        }
    }
}