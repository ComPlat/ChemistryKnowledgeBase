<?php
namespace DIQA\ChemExtension\Pages;

use IMaintainableDatabase;
use Title;


class ChemFormRepository {

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
        $this->db->query('CREATE TABLE IF NOT EXISTS chem_form (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        molecule_key VARCHAR(255) NOT NULL,
                        img_data MEDIUMBLOB NOT NULL
                    )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE chem_form ADD CONSTRAINT chem_form_molecule_key_unique UNIQUE IF NOT EXISTS (molecule_key)');
        $this->db->query('ALTER TABLE chem_form AUTO_INCREMENT=100000');

        $this->db->query('CREATE TABLE IF NOT EXISTS molecule_collection (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        publication_page_id INT(10) UNSIGNED NOT NULL,
                        molecule_collection_page_id INT(10) UNSIGNED NOT NULL,
                        molecule_page_id INT NOT NULL,
                        molecule_collection_id INT NOT NULL,
                        rgroups MEDIUMTEXT NOT NULL
                    )  ENGINE=INNODB;');
        $this->db->query('CREATE INDEX molecule_collection_page_id_index ON molecule_collection (molecule_collection_page_id);');
        $this->db->query('CREATE INDEX publication_page_id_index ON molecule_collection (publication_page_id);');
        return [ 'chem_form', 'molecule_collection' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS chem_form;');
        $this->db->query('DROP TABLE IF EXISTS molecule_collection;');

        return [ 'chem_form', 'molecule_collection' ];
    }

    public function addChemForm($moleculeKey): int
    {
        $this->db->startAtomic( __METHOD__ );
        $res = $this->db->select('chem_form', ['id'],
            ['molecule_key' => $moleculeKey ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
        } else {
            $this->db->insert('chem_form',
                [
                    'molecule_key' => $moleculeKey,
                ]);
            $id = $this->db->insertId();
        }
        $this->db->endAtomic( __METHOD__ );
        return $id;
    }

    public function getChemFormId($moleculeKey)
    {
        $res = $this->db->select('chem_form', ['id'],
            ['molecule_key' => $moleculeKey ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->id;
        }
        return null;
    }

    public function getMoleculeKey($chemFormId)
    {
        $res = $this->db->select('chem_form', ['molecule_key'],
            ['id' => $chemFormId ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->molecule_key;
        }
        return null;
    }

    public function addChemFormImage($moleculeKey, $imgData): int
    {
        $this->db->startAtomic( __METHOD__ );
        $res = $this->db->select('chem_form', ['id'],
            ['molecule_key' => $moleculeKey]
        );
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
            $this->db->update('chem_form',
                [
                    'img_data' => $imgData

                ], [
                    'molecule_key' => $moleculeKey
                ]);

        } else {
            $this->db->insert('chem_form',
                [
                    'molecule_key' => $moleculeKey,
                    'img_data' => $imgData,

                ]);
            $id = $this->db->insertId();
        }
        $this->db->endAtomic( __METHOD__ );
        return $id;
    }

    public function getChemFormImage($moleculeKey)
    {
        $res = $this->db->select('chem_form', ['img_data'],
            ['molecule_key' => $moleculeKey ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->img_data;
        }
        return null;
    }

    public function addConcreteMolecule(Title $publicationPage, Title $moleculeCollectionPage, Title $moleculePage, $moleculeCollectionId, $rGroups) {
        $this->db->insert('molecule_collection',
            [
                'publication_page_id' => $publicationPage->getArticleID(),
                'molecule_collection_page_id' => $moleculeCollectionPage->getArticleID(),
                'molecule_page_id' => $moleculePage->getArticleID(),
                'molecule_collection_id' => $moleculeCollectionId,
                'rgroups' => json_encode($rGroups),

            ]);
    }

    public function deleteAllConcreteMolecule(Title $publicationPage) {
        $this->db->delete('molecule_collection',
            [
                'publication_page_id' => $publicationPage->getArticleID(),
            ]);
    }

    public function getConcreteMolecules(Title $moleculeCollectionPage): array
    {
        $results = [];
        $res = $this->db->select('molecule_collection', ['publication_page_id', 'molecule_page_id', 'rgroups'],
            ['molecule_collection_page_id' => $moleculeCollectionPage->getArticleID() ]);
        foreach ( $res as $row ) {
            $results[] =
                [
                    'publication_page_id' => $row->publication_page_id,
                    'molecule_page_id' => $row->molecule_page_id,
                    'rGroups' => json_decode($row->rgroups),

                ];

        }
        return $results;
    }

    public function getConcreteMoleculesByKey($moleculeKey, Title $publicationPage): array
    {
        $results = [];
        $res = $this->db->select(['molecule_collection', 'chem_form'],
            ['publication_page_id', 'molecule_page_id', 'rgroups'],
            [
                'molecule_key' => $moleculeKey,
                'publication_page_id' => $publicationPage->getArticleID(),
                'chem_form.id = molecule_collection.molecule_collection_id'
            ]);
        foreach ( $res as $row ) {
            $results[] =
                [
                    'publication_page_id' => $row->publication_page_id,
                    'molecule_page_id' => $row->molecule_page_id,
                    'rGroups' => json_decode($row->rgroups),

                ];

        }
        return $results;
    }
}