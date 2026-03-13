<?php

namespace DIQA\ChemExtension\PublicationSearch;

use Wikimedia\Rdbms\IMaintainableDatabase;

class PublicationSearchRepository {
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
                        published VARCHAR(40),
                        abstract MEDIUMTEXT,
                        check_result VARCHAR(255)
                    )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE publications ADD CONSTRAINT publications_doi_key_unique UNIQUE IF NOT EXISTS (doi)');
        $this->db->query('ALTER TABLE publications ADD COLUMN IF NOT EXISTS approved TINYINT(1) DEFAULT 0;');
        return [ 'publications' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS publications;');

        return [ 'publications' ];
    }

    public function addPublication(PublicationSearchResult $result, string $checkResult = null): ?int
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

    public function updateCheckResult(PublicationSearchResult $result, string $checkResult = null): void
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

    public function updateApproved(string $doi, bool $approved): void
    {
        $this->db->update('publications',
            [
                'approved' => $approved ? 1 : 0
            ],
            [
                'doi' => $doi
            ]
        );
    }

    public function findByDoi(string $doi): ?PublicationSearchResult
    {
        $res = $this->db->select(
            'publications',
            [ 'doi', 'title', 'abstract', 'published', 'check_result', 'approved' ],
            [ 'doi' => $doi ],
            __METHOD__
        );

        if ( $res->numRows() === 0 ) {
            return null;
        }

        $row = $res->fetchObject();

        return new PublicationSearchResult(
            $row->doi,
            $row->title,
            $row->abstract,
            $row->published,
            $row->check_result,
            $row->approved
        );
    }

    public function getUnclassifiedDois(): array {
        $res = $this->db->select(
            ['publications'],
            [ 'doi' ],
            [ 'check_result' => null ],
            __METHOD__
        );
        $results = [];
        foreach ($res as $row) {
            $results[] = $row->doi;
        }
        return $results;
    }

    public function doesJobExistsForDoi(string $doi): bool {
        $title = str_replace("/", "-", $doi);
        $res = $this->db->select(
            'job',
            [ 'job_id' ],
            [ 'job_title' => $title ],
            __METHOD__
        );
        return $res->numRows() > 0;
    }

    public function getRelevantPublications($topic, $limit, $offset, bool $onlyApproved): array {

        if ($topic !== '') {
            $where = "check_result IS NOT NULL AND check_result LIKE '%$topic%'";
        } else {
            $where = "check_result IS NOT NULL AND check_result != 'not relevant'";
        }
        if ($onlyApproved) {
            $where .= " AND approved = 1";
        }
        $res = $this->db->select(
            'publications',
            [ 'doi', 'title', 'abstract', 'published', 'check_result', 'approved' ],
            [ $where ],
            __METHOD__,
            ['LIMIT' => $limit, 'OFFSET' => $offset, 'ORDER BY' => 'id DESC']
        );
        $results = [];
        foreach ($res as $row) {
            $results[] = new PublicationSearchResult($row->doi, $row->title, $row->abstract, $row->published,
                                    $row->check_result, $row->approved);
        }

        return $results;
    }

    public function getRelevantPublicationsCount($topic): int {
        if ($topic !== '') {
            $where = "check_result IS NOT NULL AND check_result LIKE '%$topic%'";
        } else {
            $where = "check_result IS NOT NULL AND check_result != 'not relevant'";
        }
        return $this->db->select(
            'publications',
            [ 'count(doi) as count' ],
            [ $where ],
            __METHOD__
        )->fetchObject()->count;
    }
}
