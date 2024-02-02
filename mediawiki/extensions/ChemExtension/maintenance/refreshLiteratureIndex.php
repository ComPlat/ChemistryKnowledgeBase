<?php


use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;
use DIQA\ChemExtension\MultiContentSave;
use DIQA\ChemExtension\Utils\WikiTools;

require_once __DIR__ . '/PageIterator.php';

class refreshLiteratureIndex extends PageIterator
{

    private $formatter;

    public function __construct()
    {
        parent::__construct();
    }

    protected function init(): void
    {
        $config = new Config([80, 20], [Config::LEFT_ALIGN, Config::LEFT_ALIGN]);
        $config->highlightWord("[REFRESHED]", Color::fromColor(COLOR::BLACK, Color::GREEN), 1);
        $config->setLeftColumnPadding(0, 3);
        $this->formatter = new Formatter($config);
    }

    protected function processPage(Title $title)
    {
        $text = WikiTools::getText($title);
        MultiContentSave::removeAllLiteratureReferencesFromIndex($title);
        MultiContentSave::parseAndUpdateLiteratureReferences($text, $title);
        echo "\n";
        echo $this->formatter->formatLine($title->getPrefixedText(), '[REFRESHED]');
    }

}

$maintClass = "refreshLiteratureIndex";
require_once RUN_MAINTENANCE_IF_MAIN;
