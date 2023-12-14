<?php

use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;

require_once __DIR__ . '/PageIterator.php';

class searchForString extends PageIterator
{

    private $formatter;
    private $patterns;

    public function __construct()
    {
        parent::__construct();
        $this->addOption('search', 'Searchstring, patterns comma-separated', true, true);

    }

    protected function init(): void
    {
        $config = new Config([80, 20], [Config::LEFT_ALIGN, Config::LEFT_ALIGN]);
        $config->highlightWord("[FOUND]", Color::fromColor(COLOR::BLACK, Color::GREEN), 1);
        $config->setLeftColumnPadding(0, 3);
        $this->formatter = new Formatter($config);
        $this->patterns = explode(",", $this->getOption('search'));
    }

    protected function processPage(Title $title)
    {
        $text = \DIQA\ChemExtension\Utils\WikiTools::getText($title);
        if ($this->containsAllPatterns($text)) {
            echo "\n";
            echo $this->formatter->formatLine($title->getPrefixedText(), '[FOUND]');
        }
    }

    private function containsAllPatterns($wikitext) {
        $all = true;
        foreach($this->patterns as $p) {
            $all &= strpos($wikitext, $p) !== false;
        }
        return $all;
    }
}

$maintClass = "searchForString";
require_once RUN_MAINTENANCE_IF_MAIN;
