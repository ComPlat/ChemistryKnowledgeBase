<?php

namespace DIQA\ChemExtension\PubChem;

use IMaintainableDatabase;


class PubChemRepository
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
        $this->db->query('CREATE TABLE IF NOT EXISTS pub_chem (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        molecule_key VARCHAR(255) NOT NULL,
                        record MEDIUMBLOB,
                        synonyms MEDIUMBLOB,
                        categories MEDIUMBLOB
                    )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE chem_form ADD CONSTRAINT pub_chem_molecule_key_unique UNIQUE IF NOT EXISTS (molecule_key)');

        return ['pub_chem'];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS pub_chem;');

        return ['pub_chem'];
    }

    public function addPubChemResult($moleculeKey, $record = null, $synonyms = null, $categories = null): int
    {
        $this->db->startAtomic(__METHOD__);
        $res = $this->db->select('pub_chem', ['id'],
            ['molecule_key' => $moleculeKey]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
            $this->db->update('pub_chem',
                [
                    'molecule_key' => $moleculeKey,
                    'record' => $record,
                    'synonyms' => $synonyms,
                    'categories' => $categories,
                ],
                [
                    'id' => $id
                ]
            );
        } else {
            $this->db->insert('pub_chem',
                [
                    'molecule_key' => $moleculeKey,
                    'record' => $record,
                    'synonyms' => $synonyms,
                    'categories' => $categories,
                ]);
            $id = $this->db->insertId();
        }
        $this->db->endAtomic(__METHOD__);
        return $id;
    }

    public function getPubChemResult($moleculeKey): ?array
    {
        $res = $this->db->select('pub_chem', ['record', 'synonyms', 'categories'],
            ['molecule_key' => $moleculeKey]);
        if ($res->numRows() === 0) {
            return null;
        }

        $row = $res->fetchObject();
        return [
            'record' => new PubChemRecordResult(json_decode($row->record)),
            'synonyms' => new PubChemSynonymsResult(json_decode($row->synonyms)),
            'categories' => new PubChemCategoriesResult(json_decode($row->categories))
        ];


    }


}