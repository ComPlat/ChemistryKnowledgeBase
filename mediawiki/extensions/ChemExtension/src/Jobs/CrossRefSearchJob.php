<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationSearch\PublicationSearchRepository;
use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\QueryUtils;
use Exception;
use Job;
use MediaWiki\MediaWikiServices;

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

        $allSubCategories = QueryUtils::getAllSubcategories('Topic');
        $prompt = <<<PROMPT
Given the following abstract of the publication, is it relevant to any of the following subcategories? 
Answer with either: yes, no or maybe. If yes or maybe, please provide also the subcategory after a semicolon. 
Subcategories:

PROMPT;

        $prompt .= join("\n", $allSubCategories);

        $this->logger->log("Prompt for AI: " . $prompt);
        $publication = $this->publicationRepo->findByDoi($doi);

        $abstract = $publication->getAbstract() !== '' ? $publication->getAbstract() : $publication->getTitle();
        $aiText = $aiClient->callAIWithTextInputs([$abstract], $prompt);

        $this->logger->log("AI response: " . $aiText);
        $parts = explode(';', $aiText);
        if ($parts[0] === 'yes' || $parts[0] === 'maybe') {
            $this->publicationRepo->updateCheckResult($publication, $parts[1] ?? 'unknown reason');
        } else {
            $this->publicationRepo->updateCheckResult($publication, 'not relevant');
        }
        return strtolower(trim($aiText));
    }


}