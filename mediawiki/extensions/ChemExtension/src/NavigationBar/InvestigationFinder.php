<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\QueryUtils;
use Philo\Blade\Blade;
use Title;

class InvestigationFinder {


    public function getInvestigationsForPublication(Title $publication, $searchTerm = ""): array
    {
        $searchTermConstraint = $searchTerm !== '' ? "[[Display title of::~*$searchTerm*]]" : "";
        $text = $publication->getText();
        $query = <<<QUERY
$searchTermConstraint
[[BelongsToPublication::$text]]
QUERY;

        $queryResults = QueryUtils::executeBasicQuery($query);
        $results = [];
        while ($row = $queryResults->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $results[$dataItem->getTitle()->getPrefixedText()] = [
                'title' => $dataItem->getTitle(),
                'type' => $this->getInvestigationType($dataItem->getTitle())
            ];
        }

        return array_values($results);

    }

    public function getInvestigationsForTopic(Title $topic, $searchTerm = ""): array
    {
        $searchTermConstraint = $searchTerm !== '' ? "[[Display title of::~*$searchTerm*]]" : "";
        $categoryTitleText = $topic->getText();
        $query = <<<QUERY
$searchTermConstraint
[[BelongsToPublication::<q>[[Category:$categoryTitleText]]</q>]]
QUERY;

        $queryResults = QueryUtils::executeBasicQuery($query, [], ['limit' => 10000]);
        $results = [];
        while ($row = $queryResults->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $results[$dataItem->getTitle()->getPrefixedText()] = [
                'title' => $dataItem->getTitle(),
                'type' => $this->getInvestigationType($dataItem->getTitle())
            ];
        }

        return array_values($results);
    }

    private function getInvestigationType($title) {
        $categories = array_keys($title->getParentCategories());
        $categories = array_diff($categories, ["Category:Investigation"]);
        return array_map(function($e) { return str_replace("_", " ", explode(":", $e)[1]); }, $categories);
    }
}