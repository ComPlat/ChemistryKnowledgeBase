<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationSearch\PublicationSearchRepository;
use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
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

        $allSubCategories = $this->getAllSubcategories('Topic');
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

    /**
     * Recursively retrieves all subcategories of a given category.
     *
     * @param string $categoryName The category name (without "Category:" prefix)
     * @param array $visited Already-visited categories (to prevent infinite loops)
     * @return string[] List of all subcategory names
     */
    private function getAllSubcategories(string $categoryName, array $visited = []): array
    {
        if (in_array($categoryName, $visited, true)) {
            return [];
        }

        $visited[] = $categoryName;
        $directSubcategories = $this->getDirectSubcategories($categoryName);
        $all = $directSubcategories;

        foreach ($directSubcategories as $subcat) {
            $nested = $this->getAllSubcategories($subcat, $visited);
            $all = array_merge($all, $nested);
        }

        return array_unique($all);
    }

    /**
     * Returns the direct subcategories of a given category from the database.
     *
     * @param string $categoryName The category name (without "Category:" prefix)
     * @return string[] List of direct subcategory names
     */
    private function getDirectSubcategories(string $categoryName): array
    {
        $dbr = \MediaWiki\MediaWikiServices::getInstance()
            ->getDBLoadBalancer()
            ->getConnection(DB_REPLICA);

        $res = $dbr->select(
            ['page', 'categorylinks'],
            ['page_title'],
            [
                'cl_to' => str_replace(' ', '_', $categoryName),
                'page_namespace' => NS_CATEGORY,
            ],
            __METHOD__,
            [],
            [
                'categorylinks' => ['JOIN', 'cl_from = page_id'],
            ]
        );

        $subcats = [];
        foreach ($res as $row) {
            $subcats[] = str_replace('_', ' ', $row->page_title);
        }

        return $subcats;
    }
}