<?php

namespace DIQA\FacetedSearch2\Update;

use MediaWiki\MediaWikiServices;
use WikiPage;

class BoostingCalculator {

    public function calculateBoosting(WikiPage $wikiPage, array &$options, array $doc): void
    {
        global $fs2gActivateBoosting;
        if (!isset($fs2gActivateBoosting) || !$fs2gActivateBoosting) {
            return;
        }

        global $fs2gDefaultBoost;
        if ($fs2gDefaultBoost) {
            $options['smwh_boost_dummy']['boost'] = $fs2gDefaultBoost;
        } else {
            $options['smwh_boost_dummy']['boost'] = 1.0;
        }

        $title = $wikiPage->getTitle();
        $namespace = $title->getNamespace();
        $pid = $wikiPage->getId();

        // add boost according to namespace
        global $fs2gNamespaceBoosts;
        if (array_key_exists($namespace, $fs2gNamespaceBoosts)) {
            $this->updateBoostFactor($options, $fs2gNamespaceBoosts[$namespace]);
        }

        // add boost according to templates
        global $fs2gTemplateBoosts;
        $templates = $this->retrieveTemplates($pid);
        $templates = array_intersect(array_keys($fs2gTemplateBoosts), $templates);
        foreach ($templates as $template) {
            $this->updateBoostFactor($options, $fs2gTemplateBoosts[$template]);
        }

        // add boost according to categories
        global $fs2gCategoryBoosts;
        $categoriesIterator = $wikiPage->getCategories();
        $categories = array();
        foreach ($categoriesIterator as $categoryTitle) {
            $categories[] = $categoryTitle;
        }
        $categories = array_intersect(array_keys($fs2gCategoryBoosts), $categories);
        foreach ($categories as $category) {
            $this->updateBoostFactor($options, $fs2gCategoryBoosts[$category]);
        }
    }

    private function updateBoostFactor(array &$options, $value): void
    {
        $options['smwh_boost_dummy']['boost'] *= $value;
    }

    private function retrieveTemplates($pageId): array
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $res = $db->newSelectQueryBuilder()
            ->select('CAST(lt_title AS CHAR) AS template')
            ->from('templatelinks')
            ->join('page', null, ['page_id = tl_from'])
            ->join('linktarget', null, ['lt_id = tl_target_id'])
            ->where("tl_from = $pageId")
            ->caller(__METHOD__)
            ->fetchResultSet();

        $smwhTemplates = [];
        if ($res->numRows() > 0) {
            while ($row = $res->fetchObject()) {
                $template = $row->template;
                $smwhTemplates[] = str_replace("_", " ", $template);
            }
        }
        $res->free();

        return $smwhTemplates;
    }
}
