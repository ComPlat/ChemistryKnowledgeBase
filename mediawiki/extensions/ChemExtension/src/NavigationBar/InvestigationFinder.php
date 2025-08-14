<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use eftec\bladeone\BladeOne;
use Title;
use OutputPage;

class InvestigationFinder
{


    public function getInvestigationsForPublication(Title $publication, $searchTerm = ""): array
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        $searchtext = $dbr->addQuotes(strtolower("%$searchTerm%"));
        $publication = $dbr->addQuotes($publication->getDBkey());
        $sql = <<<SQL
SELECT DISTINCT page_title, page_namespace FROM page 
    JOIN page_props props ON page_id = props.pp_page
    WHERE LOWER(CONVERT(pp_value USING latin1)) LIKE $searchtext 
    AND page_title LIKE CONCAT($publication,'/%');
SQL;
        $res = $dbr->query($sql);
        $results = [];
        foreach ($res as $row) {
            $title = \Title::newFromText($row->page_title, $row->page_namespace);
            if ($title->isRedirect()) {
                continue;
            }
            $results[$title->getPrefixedText()] = [
                'title' => $title,
                'type' => $this->getInvestigationType($title)];

        }

        return array_values($results);

    }

    public function getInvestigationsForTopic(Title $topic, $searchTerm = ""): array
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $category = $dbr->addQuotes($topic->getDBkey());
        $res = $dbr->select('page', 'page_id', "page_title = $category AND page_namespace = " . NS_CATEGORY);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $category_id = $row->page_id;
        } else {
            return [];
        }

        $searchtext = $dbr->addQuotes(strtolower("%$searchTerm%"));
        $sql = <<<SQL
SELECT DISTINCT t1.page_title, t1.page_namespace FROM page t1 JOIN 
    ( SELECT page_title FROM page p 
        JOIN page_props props ON p.page_id = props.pp_page
        JOIN category_index ON p.page_id = category_index.page_id AND category_index.category_id = $category_id
        WHERE LOWER(CONVERT(pp_value USING latin1)) LIKE $searchtext
       
    ) t2 ON t1.page_title LIKE CONCAT(t2.page_title,'/%');
SQL;
        $res = $dbr->query($sql);
        $results = [];
        foreach ($res as $row) {
            $title = \Title::newFromText($row->page_title, $row->page_namespace);
            $results[$title->getPrefixedText()] = [
                'title' => $title,
                'type' => $this->getInvestigationType($title)];

        }

        return array_values($results);
    }

    private function getInvestigationType($title)
    {
        $categories = array_keys($title->getParentCategories());
        $categories = array_diff($categories, ["Category:Investigation"]);
        return array_map(function ($e) {
            return str_replace("_", " ", explode(":", $e)[1]);
        }, $categories);
    }

    public static function renderInvestigationList(OutputPage $out)
    {
        global $wgTitle;
        $finder = new InvestigationFinder();
        $investigations = $finder->getInvestigationsForPublication($wgTitle);
        if (count($investigations) === 0) {
            return;
        }
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new BladeOne ($views, $cache);

        $html = $blade->run("investigation-list",
            [
                'list' => $investigations,
            ]
        );
        $out->addHTML($html);
    }
}