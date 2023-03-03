<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\QueryUtils;
use Philo\Blade\Blade;
use Title;

class InvestigationFinder {


    public function getInvestigationsForPublication(Title $publication): array
    {
        $text = $publication->getText();
        $query = <<<QUERY
[[BelongsToPublication::$text]]
QUERY;

        $queryResults = QueryUtils::executeBasicQuery($query);
        $results = [];
        while ($row = $queryResults->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $results[$dataItem->getTitle()->getPrefixedText()] = $dataItem->getTitle();
        }

        return array_values($results);

    }

    public function getInvestigationsForTopic(Title $topic): array
    {
        $categoryTitleText = $topic->getText();
        $query = <<<QUERY
[[BelongsToPublication::<q>[[Category:$categoryTitleText]]</q>]]
QUERY;

        $queryResults = QueryUtils::executeBasicQuery($query, [], ['limit' => 10000]);
        $results = [];
        while ($row = $queryResults->getNext()) {
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $results[$dataItem->getTitle()->getPrefixedText()] = $dataItem->getTitle();
        }

        return array_values($results);
    }

}