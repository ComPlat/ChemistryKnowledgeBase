<?php

namespace DIQA\ChemExtension\CrossRef;

use Wikimedia\Rdbms\IMaintainableDatabase;

class CrossRefRepository {
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
        $this->db->query('CREATE TABLE IF NOT EXISTS publications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        doi VARCHAR(255) NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        published VARCHAR(40) NOT NULL,
                        abstract MEDIUMTEXT,
                        check_result VARCHAR(255)
                    )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE publications ADD CONSTRAINT publications_doi_key_unique UNIQUE IF NOT EXISTS (doi)');

        return [ 'publications' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS publications;');

        return [ 'publications' ];
    }

    public function addPublication(CrossRefResult $result, string $checkResult = null): ?int
    {

        if ($this->findByDoi($result->getDoi()) !== null) {
            return null;
        }

        $this->db->insert('publications',
            [
                'doi' => $result->getDoi(),
                'title' => $result->getTitle(),
                'abstract' => $result->getAbstract(),
                'published' => $result->getPublished(),
                'check_result' => $checkResult,
            ]);
        return $this->db->insertId();
    }

    public function updateCheckResult(CrossRefResult $result, string $checkResult = null)
    {
        $this->db->update('publications',
            [
                'check_result' => $checkResult
            ],
            [
                'doi' => $result->getDoi()
            ]
        );
    }

    public function findByDoi(string $doi): ?CrossRefResult
    {
        $res = $this->db->select(
            'publications',
            [ 'doi', 'title', 'abstract', 'published' ],
            [ 'doi' => $doi ],
            __METHOD__
        );

        if ( $res->numRows() === 0 ) {
            return null;
        }

        $row = $res->fetchObject();

        return new CrossRefResult(
            $row->doi,
            $row->title,
            $row->abstract,
            $row->published
        );
    }

    public function isClassified(string $doi): bool
    {
        $res = $this->db->select(
            'publications',
            [ 'check_result' ],
            [ 'doi' => $doi ],
            __METHOD__
        );

        if ( $res->numRows() === 0 ) {
            return false;
        }

        $row = $res->fetchObject();

        return !is_null($row->check_result);
    }
}
