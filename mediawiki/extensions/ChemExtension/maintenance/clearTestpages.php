<?php

use MediaWiki\MediaWikiServices;
use DIQA\Formatter\Config;
use DIQA\Formatter\Color;
use DIQA\Formatter\Formatter;

require_once __DIR__ . '/PageIterator.php';

class clearTestpages extends PageIterator
{

    private $formatter;

    public function __construct()
    {
        parent::__construct();
        $this->addOption('delete', 'Actually delete pages', false, false);
        print "Clearing testpages!\n\n";
    }

    protected function init(): void
    {
        $config = new Config([80, 20], [Config::LEFT_ALIGN, Config::LEFT_ALIGN]);
        $config->highlightWord("[DELETED]", Color::fromColor(COLOR::BLACK, Color::GREEN), 1);
        $config->highlightWord("[FAILED]", Color::fromColor(COLOR::BLACK, Color::RED), 1);
        $config->setLeftColumnPadding(0,3);
        $this->formatter = new Formatter($config);
    }

    protected function processPage(Title $title)
    {
        if (!self::startsWith($title->getText(), 'Test')) {
            return;
        }
        echo "\n";
        if (!$this->hasOption('delete')) {
            echo $this->formatter->formatLine($title->getPrefixedText(), '[WOULD BE DELETED]');
            return;
        }
        $services = MediaWikiServices::getInstance();
        $deleter = $services->getUserFactory()->newFromName("WikiSysop");
        $page = $services->getWikiPageFactory()->newFromTitle($title);
        $deletePage = $services->getDeletePageFactory()->newDeletePage($page, $deleter);
        $deleteStatus = $deletePage
            ->deleteIfAllowed("clearTestpages");
        if ($deleteStatus->isOK()) {
            echo $this->formatter->formatLine($title->getPrefixedText(), '[DELETED]');
        } else {
            echo $this->formatter->formatLine($title->getPrefixedText(), '[FAILED]');
        }
    }

    private static function startsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        return substr( $haystack, 0, $length ) === $needle;
    }


}

$maintClass = "clearTestpages";
require_once RUN_MAINTENANCE_IF_MAIN;
