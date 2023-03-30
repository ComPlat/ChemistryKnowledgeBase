<?php

namespace DIQA\ChemExtension;

use Title;
use Wikimedia\Rdbms\IMaintainableDatabase;

class CategoryIndexRepository
{
    private $db;

    /**
     * @param IMaintainableDatabase $db
     */
    public function __construct(IMaintainableDatabase $db)
    {
        $this->db = $db;
    }

    public function setupTables()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS category_index (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        page_id INT NOT NULL,
                        category_id INT NOT NULL
                    )  ENGINE=INNODB;');

        $this->db->query('CREATE INDEX IF NOT EXISTS category_index_category_id_index ON category_index (category_id);');
        return ['category_index'];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS category_index;');
        return ['category_index'];
    }

    public function addCategoriesForTitle(Title $title, $categories)
    {
        $this->db->startAtomic(__METHOD__);
        foreach ($categories as $category) {
            $this->db->insert('category_index',
                [
                    'page_id' => $title->getArticleID(),
                    'category_id' => $category->getArticleID(),
                ]);
        }
        $this->db->endAtomic(__METHOD__);
    }

    public function deleteCategoryFromIndex($title) {
        $this->db->delete('category_index',
            ['page_id' => $title->getArticleID()]);
    }

    public function getTitlesForCategory(Title $category): array
    {
        $results = [];
        $res = $this->db->select('category_index', ['page_id'],
            ['category_id' => $category->getArticleID()]);
        foreach ($res as $row) {
            $results[] = Title::newFromID($row->page_id);
        }

        return $results;
    }
}