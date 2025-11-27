<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use MediaWiki\Content\WikitextContent;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use WikiPage;

class PublicationTaggingJob extends Job
{
    private LoggerUtils $logger;

    public function __construct($title)
    {
        parent::__construct('PublicationTaggingJob', $title);
        $this->logger = new LoggerUtils('PublicationTaggingJob', 'ChemExtension');
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        try {


            $tags = $this->tagPublicationPage();
            $wikitext = WikiTools::getText($this->getTitle());
            $te = new TemplateEditor($wikitext);
            $tagsCommaSeparated = implode(",", $tags);

            if ($te->exists('Tags')) {
                $this->logger->log("Tags template exists, updating...");
                $wikitext = $te->replaceTemplateParameters('Tags', ['tags' => $tagsCommaSeparated]);
            } else {
                $tagsContent = "{{Tags|tags=" . $tagsCommaSeparated . "}}";
                $wikitext .= "\n" . $tagsContent;
            }
            $this->logger->log("New tags: " . $tagsCommaSeparated);

            WikiTools::doEditContent($this->getTitle(), $wikitext, "auto-generated");
            $this->logger->log("Tagged publication page: " . $this->getTitle()->getPrefixedText());
            $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
            $hooksContainer->run('CleanupChemExtState');

        } catch (Exception $e) {
            $this->logger->error("ERROR: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function tagPublicationPage(): array
    {
        global $IP;

        $wikiPage = new WikiPage($this->getTitle());
        $content = $wikiPage->getContent();
        if (!$content) {
            throw new Exception("Cannot find content for: " . $this->getTitle()->getPrefixedText());
        }

        // remove existing tags before sending it to AI
        $te = new TemplateEditor($content->getText());
        $wikitext = $te->replaceTemplateParameters('Tags', ['tags' => '']);
        $content = new WikitextContent($wikitext);

        $parserOut = MediaWikiServices::getInstance()->getContentRenderer()->getParserOutput($content, $wikiPage);
        $text = Sanitizer::stripAllTags($parserOut->getText() ?? '');

        $aiClient = new AIClient();
        $prompt = "$IP/extensions/ChemExtension/resources/ai-prompts/tagging_prompt.txt";
        $aiText = $aiClient->callAIWithTextInputs([$text], file_get_contents($prompt));
        return explode(",", $aiText);

    }

}
