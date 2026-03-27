<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationSearch\PublicationSearchRepository;
use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\PublicationSearch\PublicationSearchResult;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\QueryUtils;
use Exception;
use Job;
use MediaWiki\Context\RequestContext;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Revision\SlotRecord;
use WikiPage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class CrossRefSearchJob extends Job
{

    private $logger;
    private $doi;
    private $publicationRepo;

    public function __construct($title, $params)
    {
        $this->doi = $params['doi'] ?? null;
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->publicationRepo = new PublicationSearchRepository($dbr);

        $params['title'] = $title;
        parent::__construct('CrossRefSearchJob', $params);
        $this->logger = new LoggerUtils('CrossRefSearchJob', 'ChemExtension');

    }

    public function run()
    {
        try {
            if (is_null($this->doi)) {
                return;
            }
            $this->logger->log("CrossRefSearchJob with doi {$this->doi}");
            $this->checkPublication($this->doi);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }


    private function checkPublication(string $doi)
    {
        $aiClient = new AIClient();

        $promptTitle = Title::newFromText('Publication_search_prompt', NS_MEDIAWIKI);
        if ($promptTitle->exists()) {
            $prompt = $this->renderPage($promptTitle);
        } else {
            $allTopics = QueryUtils::getAllSubcategories('Topic');
            $specificPrompts = $this->getTopicsWithSpecificPrompts();
            $nonSpecificPrompts = array_diff($allTopics, $specificPrompts);
            $prompt = <<<PROMPT
    Given the following abstract of the publication, is it relevant to any of the following subcategories? 
    Answer with either: yes, no or maybe. If yes or maybe, please provide also the subcategory after a semicolon. 
    Subcategories:
    
    PROMPT;

            $prompt .= join("\n", $nonSpecificPrompts);

        }

        $publication = $this->publicationRepo->findByDoi($doi);

        $abstract = $publication->getAbstract() !== '' ? $publication->getAbstract() : $publication->getTitle();
        $aiText = $aiClient->callAIWithTextInputs([$abstract], $prompt);

        $this->logger->log("AI response: " . $aiText);
        $parts = explode(';', $aiText);
        if (strtolower($parts[0]) === 'yes' || strtolower($parts[0]) === 'maybe') {
            $this->logger->log("Publication relevant ({$parts[0]}): " . $publication->getDoi());
            $this->publicationRepo->updateCheckResult($publication, $parts[1] ?? 'unknown reason');
        } else {
            $allTopics = QueryUtils::getAllSubcategories('Topic');
            $positiveResults = $this->checkSpecificPrompts($publication, $allTopics);
            if (count($positiveResults) > 0) {
                $topicResults = join(',', $positiveResults);
                $this->logger->log("Publication relevant [topics: $topicResults]: " . $publication->getDoi());
                $this->publicationRepo->updateCheckResult($publication, $topicResults);
            } else {
                $this->logger->log("Publication not relevant: " . $publication->getDoi());
                $this->publicationRepo->updateCheckResult($publication, 'not relevant');
            }
        }
        return strtolower(trim($aiText));
    }

    private function renderPage(Title $promptTitle): string
    {
        $wikiPage = new WikiPage($promptTitle);
        $content = $wikiPage->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();
        $parser = clone MediaWikiServices::getInstance()->getParser();
        $parserOutput = $parser->parse($content, $promptTitle, new ParserOptions(RequestContext::getMain()->getUser()));
        $renderedText = $parserOutput->getText(['enableSectionEditLinks' => false]);
        return Sanitizer::stripAllTags($renderedText);
    }

    private function checkSpecificPrompts(PublicationSearchResult $publication, array $allTopics): array
    {
        $aiClient = new AIClient();

        $positiveResults = [];
        foreach ($allTopics as $topic) {
            $promptTitle = Title::newFromText('Prompt_' . $topic , NS_MEDIAWIKI);
            if (!$promptTitle->exists()) {
                continue;
            }
            $prompt = $this->renderPage($promptTitle);
            $this->logger->log("Specific prompt for AI [topic:$topic]: " . $prompt);
            $publication = $this->publicationRepo->findByDoi($publication->getDoi());

            $abstract = $publication->getAbstract() !== '' ? $publication->getAbstract() : $publication->getTitle();
            $aiText = $aiClient->callAIWithTextInputs([$abstract], $prompt);
            $this->logger->log("AI response: " . $aiText);
            $parts = explode(';', $aiText);

            if (strtolower($parts[0]) === 'yes' || strtolower($parts[0]) === 'maybe') {
                $this->logger->log("Publication relevant [topic:$topic] (response: {$parts[0]}): " . $publication->getDoi());
                $positiveResults[] = $topic;
            }
        }

        return $positiveResults;

    }

    private function getTopicsWithSpecificPrompts(): array
    {
        $results = [];
        $allTopics = QueryUtils::getAllSubcategories('Topic');
        foreach ($allTopics as $topic) {
            $promptTitle = Title::newFromText('Prompt_' . $topic, NS_MEDIAWIKI);
            if ($promptTitle->exists()) {
                 $results[] = $topic;
            }
        }
        return $results;
    }
}