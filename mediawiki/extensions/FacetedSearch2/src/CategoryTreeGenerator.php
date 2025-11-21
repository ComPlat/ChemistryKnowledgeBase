<?php

namespace DIQA\FacetedSearch2;

use Wikimedia\Rdbms\IDatabase;


class CategoryTreeGenerator
{
    private $db;

    public function __construct(IDatabase $db)
    {
        $this->db = $db;
    }

    public function getCategoryTuples()
    {
        $CATEGORY_NAMESPACE = NS_CATEGORY;
        $sql = <<<SQL
SELECT DISTINCT page_from.page_title AS from_category, cl_to AS to_category,
       props_from.pp_value AS from_displaytitle, props_to.pp_value AS to_displaytitle

FROM page page_from
LEFT JOIN categorylinks ON page_from.page_id = categorylinks.cl_from
LEFT JOIN page_props props_from ON props_from.pp_page = page_from.page_id AND props_from.pp_propname = 'displaytitle'

LEFT JOIN page AS page_to ON categorylinks.cl_to = page_to.page_title AND page_to.page_namespace = $CATEGORY_NAMESPACE
LEFT JOIN page_props props_to ON props_to.pp_page = page_to.page_id AND props_to.pp_propname = 'displaytitle'
WHERE page_from.page_namespace = $CATEGORY_NAMESPACE
SQL;

        $res = $this->db->query($sql);
        $results = [];
        foreach ($res as $row) {
            $results[] =
                [
                    'from' => $row->from_category,
                    'to' => $row->to_category,
                    'from_displaytitle' => $row->from_displaytitle ?? str_replace("_", " ", $row->from_category),
                    'to_displaytitle' => $row->to_displaytitle ?? str_replace("_", " ", $row->to_category ?? ''),
                ];

        }
        return $results;
    }
}
