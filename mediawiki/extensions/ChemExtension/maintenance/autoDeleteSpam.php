<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentityValue;


/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Auto-deletes all pages in the spam category
 */
class autoDeleteSpam extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Deletes all new pages (1 day old) which are considered spam by the AI');
        $this->addOption('contains', 'Only process publications that contain this string');
        $this->addOption('dryrun', 'Does not actually create jobs, just show the list of publications');
        $this->addOption('days', 'Considers last x days as spam (default: 1)');
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        $days = $this->getOption('days', 1);
        $yesterday = date("Y-m-d", strtotime("-$days day"));
        $results = QueryUtils::executeBasicQuery("[[Modification date::>$yesterday]]");
        $pages = [];
        while ($row = $results->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            if ($dataItem === false) continue;
            if (str_starts_with($dataItem->getTitle()->getText(), "Test")) continue;
            $pages[] = $dataItem->getTitle();
        }
        foreach($pages as $page) {
            if ($this->hasOption('contains')
                && !str_contains($page->getPrefixedText(), $this->getOption('contains'))) {
                continue;
            }
            if (!$page->exists()) continue;
            if ($page->getNamespace() != NS_MAIN) {
                continue;
            }
            echo "\nProcessing publication: " . $page->getPrefixedText() . "...";
            if (!$this->checkForSpam($page)) {
                echo "not spam";
                continue;
            }
            $deleter = new UltimateAuthority( new UserIdentityValue( 0, User::MAINTENANCE_SCRIPT_USER ) );
            if (!$this->hasOption("dryrun")) {
                $wikiPage = MediaWikiServices::getInstance()->getDeletePageFactory()->newDeletePage(new \WikiPage($page), $deleter);
                $wikiPage->deleteIfAllowed("Spam");
                echo "deleted";
            } else {
                echo "would be deleted";
            }
        }
        echo "\n";
    }

    private function checkForSpam(Title $title): bool
    {
        global $IP;

        $wikiPage = new \WikiPage($title);
        $content = $wikiPage->getContent();
        if (!$content) {
            throw new Exception("Cannot find content for: " . $title->getPrefixedText());
        }

        $parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wikiPage);
        $text = Sanitizer::stripAllTags($parserOut->getText() ?? '');

        $aiClient = new AIClient();
        $prompt = "$IP/extensions/ChemExtension/resources/ai-prompts/check_for_spam.txt";
        $aiText = $aiClient->callAIWithTextInputs([$text], file_get_contents($prompt));
        return trim($aiText) === 'yes';

    }



}

$maintClass = autoDeleteSpam::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
