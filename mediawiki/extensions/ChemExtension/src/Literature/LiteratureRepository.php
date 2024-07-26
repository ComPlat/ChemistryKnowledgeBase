<?php

namespace DIQA\ChemExtension\Literature;

use Wikimedia\Rdbms\IMaintainableDatabase;

class LiteratureRepository {
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
        $this->db->query('CREATE TABLE IF NOT EXISTS literature (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        doi VARCHAR(255) NOT NULL,
                        data MEDIUMTEXT
                    )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE literature ADD CONSTRAINT literature_doi_key_unique UNIQUE (doi)');

        $this->db->query('CREATE TABLE IF NOT EXISTS literature_index (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        doi VARCHAR(255) NOT NULL,
                        page_id INT(10) UNSIGNED NOT NULL
                    )  ENGINE=INNODB;');

        return [ 'literature', 'literature_index' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS literature;');
        $this->db->query('DROP TABLE IF EXISTS literature_index;');

        return [ 'literature', 'literature_index' ];
    }

    public function addLiterature($doi, $data): int
    {

        $res = $this->db->select('literature', ['id'],
            ['doi' => $doi ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
            $this->db->update('literature',
                [
                    'data' => $data,
                ], [
                    'doi' => $doi
                ]);
        } else {
            $this->db->insert('literature',
                [
                    'doi' => $doi,
                    'data' => $data,
                ]);
            $id = $this->db->insertId();
        }

        return $id;
    }

    public function addToLiteratureIndex($doi, $title): int
    {
        $this->db->insert('literature_index',
            [
                'doi' => $doi,
                'page_id' => $title->getArticleID(),
            ]);
        return $this->db->insertId();
    }

    public function deleteIndexForPage($title) {
        $this->db->delete('literature_index', [
            'page_id' => $title->getArticleID(),
        ]);
    }

    public function getPagesForDOI($doi): array
    {
        $res = $this->db->select('literature_index', ['page_id'],
            ['doi' => $doi ]);
        $results = [];
        foreach ($res as $row) {
            $results[] = \Title::newFromID($row->page_id);
        }
        return $results;
    }

    public function addLiteraturePlaceholder($doi): int {
        return $this->addLiterature($doi, '__placeholder__');
    }

    public function getLiterature($doi)
    {
        $res = $this->db->select('literature', ['doi', 'data'],
            ['doi' => $doi ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return ['doi' => $row->doi, 'data' => $row->data === '__placeholder__' ? $row->data : json_decode($row->data)];
        }
        return null;
    }
}