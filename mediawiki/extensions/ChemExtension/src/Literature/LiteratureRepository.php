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
        $this->db->query('ALTER TABLE literature ADD CONSTRAINT literature_doi_key_unique UNIQUE IF NOT EXISTS (doi)');

        return [ 'literature' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS literature;');

        return [ 'literature' ];
    }

    public function addLiterature($doi, $data): int
    {
        $this->db->startAtomic( __METHOD__ );
        $res = $this->db->select('literature', ['id'],
            ['doi' => $doi ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
        } else {
            $this->db->insert('literature',
                [
                    'doi' => $doi,
                    'data' => $data,
                ]);
            $id = $this->db->insertId();
        }
        $this->db->endAtomic( __METHOD__ );
        return $id;
    }

    public function getLiterature($doi)
    {
        $res = $this->db->select('literature', ['doi', 'data'],
            ['doi' => $doi ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return ['doi' => $row->doi, 'data' => json_decode($row->data)];
        }
        return null;
    }
}