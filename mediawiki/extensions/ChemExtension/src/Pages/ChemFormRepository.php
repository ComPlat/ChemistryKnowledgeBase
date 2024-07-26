<?php

namespace DIQA\ChemExtension\Pages;

use IMaintainableDatabase;
use Title;


class ChemFormRepository
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
        $this->db->query('CREATE TABLE IF NOT EXISTS chem_form (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        molecule_key VARCHAR(255) NOT NULL,
                        img_data MEDIUMBLOB NOT NULL
                    )  ENGINE=INNODB;');

        $this->db->query('ALTER TABLE chem_form ADD CONSTRAINT chem_form_molecule_key_unique UNIQUE (molecule_key)');
        $this->db->query('ALTER TABLE chem_form AUTO_INCREMENT=100000');

        $this->db->query('CREATE TABLE IF NOT EXISTS chem_form_index (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        page_id INT NOT NULL,
                        chem_form_id INT NOT NULL
                    )  ENGINE=INNODB;');

        $this->db->query('CREATE INDEX chem_form_index_chem_form_id_index ON chem_form_index (chem_form_id);');

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
        return ['chem_form', 'chem_form_index', 'molecule_collection'];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS chem_form;');
        $this->db->query('DROP TABLE IF EXISTS chem_form_index;');
        $this->db->query('DROP TABLE IF EXISTS molecule_collection;');

        return ['chem_form', 'chem_form_index', 'molecule_collection'];
    }

    public function addChemForm($moleculeKey): int
    {
        $this->db->startAtomic(__METHOD__);
        $res = $this->db->select('chem_form', ['id'],
            ['molecule_key' => $moleculeKey]);
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
        $this->db->endAtomic(__METHOD__);
        return $id;
    }

    public function getChemFormId($moleculeKey)
    {
        $res = $this->db->select('chem_form', ['id'],
            ['molecule_key' => $moleculeKey]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->id;
        }
        return null;
    }


    public function getMoleculeKey($chemFormId)
    {
        $res = $this->db->select('chem_form', ['molecule_key'],
            ['id' => $chemFormId]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->molecule_key;
        }
        return null;
    }

    public function deleteChemForm($chemFormId) {
        $this->db->delete('chem_form',
            ['id' => $chemFormId]);
    }

    public function addOrUpdateChemFormImage($moleculeKey, $imgData): int
    {
        $this->db->startAtomic(__METHOD__);
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
        $this->db->endAtomic(__METHOD__);
        return $id;
    }

    public function updateImageAndMoleculeKey($chemformid, $moleculeKey, $imgData): int
    {
        $this->db->update('chem_form',
            [
                'molecule_key' => $moleculeKey,
                'img_data' => $imgData
            ], [
                'id' => $chemformid
            ]);
        return $chemformid;
    }

    public function replaceMoleculeKeyAndImage($moleculeKeyOld, $moleculeKeyNew, $imgData)
    {
        $this->db->update('chem_form',
            [
                'img_data' => $imgData,
                'molecule_key' => $moleculeKeyNew

            ], [
                'molecule_key' => $moleculeKeyOld
            ]);
    }

    public function commitReservedMolecule($moleculeKeyReserved)
    {
        $this->db->update('chem_form',
            [
                'molecule_key' => $moleculeKeyReserved,

            ], [
                'molecule_key' => "reserved-".$moleculeKeyReserved
            ]);

    }

    public function getChemFormImageByKey($moleculeKey)
    {
        $res = $this->db->select('chem_form', ['img_data'],
            ['molecule_key' => $moleculeKey]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->img_data;
        }

        return null;
    }

    public function getChemFormImageById($chemFormId)
    {
        $res = $this->db->select('chem_form', ['img_data'],
            ['id' => $chemFormId]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->img_data;
        }
        return null;
    }

    public function hasChemFormImageById($chemFormId)
    {
        $res = $this->db->select('chem_form', ['id'],
            ['id' => $chemFormId,
                'img_data != ""']);

        return $res->numRows() > 0;
    }

    public function addConcreteMolecule(Title $publicationPage, Title $moleculeCollectionPage, Title $moleculePage, $moleculeCollectionId, $rGroups)
    {

        $this->db->insert('molecule_collection',
            [
                'publication_page_id' => $publicationPage->getArticleID(),
                'molecule_collection_page_id' => $moleculeCollectionPage->getArticleID(),
                'molecule_page_id' => $moleculePage->getArticleID(),
                'molecule_collection_id' => $moleculeCollectionId,
                'rgroups' => json_encode($rGroups),

            ]);
    }

    public function deleteAllConcreteMoleculeByCollectionId(Title $publicationPage, $moleculeCollectionId)
    {
        $this->db->delete('molecule_collection',
            [
                'publication_page_id' => $publicationPage->getArticleID(),
                'molecule_collection_id' => $moleculeCollectionId
            ]);
    }

    public function getAllConcreteMolecule(Title $publicationPage): array
    {
        $res = $this->db->select('molecule_collection', ['molecule_page_id', 'rgroups'],
            [
                'publication_page_id' => $publicationPage->getArticleID(),
            ]);
        $results = [];
        foreach ($res as $row) {
            $results[] =
                [
                    'molecule_page_id' => $row->molecule_page_id,
                    'rGroups' => json_decode($row->rgroups),
                ];
        }
        return $results;
    }

    public function deleteAllConcreteMoleculeByMoleculePage(Title $moleculePage)
    {
        $this->db->delete('molecule_collection',
            [
                'molecule_page_id' => $moleculePage->getArticleID(),
            ]);
    }

    public function getConcreteMolecules(Title $moleculeCollectionPage): array
    {
        $results = [];
        $res = $this->db->select('molecule_collection', ['publication_page_id', 'molecule_page_id', 'rgroups'],
            ['molecule_collection_page_id' => $moleculeCollectionPage->getArticleID()]);
        foreach ($res as $row) {
            $results[] =
                [
                    'publication_page_id' => $row->publication_page_id,
                    'molecule_page_id' => $row->molecule_page_id,
                    'rGroups' => json_decode($row->rgroups),

                ];

        }
        return $results;
    }

    public function getPublicationPageForConcreteMolecule(Title $moleculePage): ?Title
    {
        $res = $this->db->select('molecule_collection', [ 'DISTINCT publication_page_id'],
            ['molecule_page_id' => $moleculePage->getArticleID()]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return Title::newFromID($row->publication_page_id);
        }
        return null;
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
        foreach ($res as $row) {
            $results[] =
                [
                    'publication_page_id' => $row->publication_page_id,
                    'molecule_page_id' => $row->molecule_page_id,
                    'rGroups' => json_decode($row->rgroups),

                ];

        }
        return $results;
    }

    public function deleteAllChemFormIndexByPage(Title $page)
    {
        $this->db->delete('chem_form_index',
            [
                'page_id' => $page->getArticleID(),
            ]);
    }

    public function deleteAllChemFormIndexByChemFormId($chemformId)
    {
        $this->db->delete('chem_form_index',
            [
                'chem_form_id' => $chemformId,
            ]);
    }

    public function addChemFormToIndex(Title $page, $chemformId)
    {
        $this->db->insert('chem_form_index',
            [
                'page_id' => $page->getArticleID(),
                'chem_form_id' => $chemformId
            ]);
    }

    public function getPagesByChemFormId($chemformId)
    {
        $res = $this->db->select('chem_form_index', ['page_id'],
            ['chem_form_id' => $chemformId]);
        $results = [];
        foreach ($res as $row) {
            $results[] = Title::newFromID($row->page_id);
        }
        return $results;
    }

    public function getChemFormIdsByPages(array $titles): array
    {
        if (count($titles) === 0) {
            return [];
        }
        $ids = array_map(function($e) { return $e->getArticleID();}, $titles);
        $res = $this->db->select('chem_form_index',
            ['chem_form_id'],
            ['page_id' =>$ids ]);
        $results = [];
        foreach ($res as $row) {
            $results[] = $row->chem_form_id;
        }
        return $results;
    }

    public function getUnusedMoleculeIds($limit = 10000, $offset = 0)
    {
        $res = $this->db->select(
            ['chem_form_index', 'chem_form'],
            ['chem_form.id'],
            ['chem_form_index.chem_form_id IS NULL'],
            __METHOD__,
            [
                'OFFSET' => $offset,
                'LIMIT' => $limit],
            ['chem_form_index' => ['LEFT JOIN', 'chem_form_index.chem_form_id=chem_form.id']]);
        $results = [];
        foreach ($res as $row) {
            $results[] = $row->id;


        }
        return $results;
    }

    public function getMoleculeIdsUsedOnCategory(Title $category): array
    {
        $res = $this->db->select(
            ['chem_form_index', 'category_index'],
            ['chem_form_index.chem_form_id'],
            ['chem_form_index.page_id = category_index.page_id',
                'category_id' =>  $category->getArticleID()
            ]
        );
        $results = [];
        foreach ($res as $row) {
            $results[] = $row->chem_form_id;
        }
        return $results;
    }
}