<?php
namespace DIQA\ChemExtension\Pages;

use IMaintainableDatabase;
use MediaWiki\MediaWikiServices;
use User;


class ChemFormRepository {

    const BASE_ID = 100000;

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
                        chem_form_key VARCHAR(255) NOT NULL
                    )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE chem_form ADD CONSTRAINT chem_form_chem_form_key_unique UNIQUE IF NOT EXISTS (chem_form_key)');

        $this->db->query('CREATE TABLE IF NOT EXISTS chem_form_img (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        chem_form_key VARCHAR(255) NOT NULL,
                     
                        img_data MEDIUMBLOB NOT NULL
                    )  ENGINE=INNODB;');
        return [ 'chem_form', 'chem_form_img' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS chem_form;');
        $this->db->query('DROP TABLE IF EXISTS chem_form_img;');

        return [ 'chem_form', 'chem_form_img' ];
    }

    public function addChemForm($chemFormKey): int
    {
        $this->db->startAtomic( __METHOD__ );
        $res = $this->db->select('chem_form', ['id'],
            ['chem_form_key' => $chemFormKey ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
        } else {
            $this->db->insert('chem_form',
                [
                    'chem_form_key' => $chemFormKey,
                ]);
            $id = $this->db->insertId();
        }
        $this->db->endAtomic( __METHOD__ );
        return self::BASE_ID + $id;
    }

    public function getChemFormId($chemFormKey)
    {
        $res = $this->db->select('chem_form', ['id'],
            ['chem_form_key' => $chemFormKey ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return self::BASE_ID + $row->id;
        }
        return null;
    }

    public function addChemFormImage($chemFormKey, $imgData): int
    {
        $this->db->startAtomic( __METHOD__ );
        $res = $this->db->select('chem_form_img', ['id'],
            ['chem_form_key' => $chemFormKey,
              ]
        );
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            $id = $row->id;
        } else {
            $this->db->insert('chem_form_img',
                [
                    'chem_form_key' => $chemFormKey,
                    'img_data' => $imgData,

                ]);
            $id = $this->db->insertId();
        }
        $this->db->endAtomic( __METHOD__ );
        return $id;
    }

    public function getChemFormImage($chemFormKey)
    {
        $res = $this->db->select('chem_form_img', ['img_data'],
            ['chem_form_key' => $chemFormKey ]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $row->img_data;
        }
        return null;
    }
}