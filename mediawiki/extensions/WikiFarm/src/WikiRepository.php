<?php
namespace DIQA\WikiFarm;

use IMaintainableDatabase;
use MediaWiki\MediaWikiServices;
use User;


class WikiRepository {

    private $db;

    /**
     * @param IMaintainableDatabase $db
     */
    public function __construct(IMaintainableDatabase $db)
    {
        $this->db = $db;
    }


    public function createWikiJob($name, $user) {
        $title = \Title::newFromText( "Wiki $name/CreateWikiJob" );
        $jobParams = [ 'name' => $name ];
        $userId = \User::idFromName($user);
        if (is_null($userId)) {
            print "\nUser '$user' does not exist\n";
            exit;
        }
        $wikiId = self::createWikiInDB($name, $userId);
        $jobParams['wikiId'] = "$wikiId";
        $job = new CreateWikiJob( $title, $jobParams );
        \JobQueueGroup::singleton()->push( $job );
        return $wikiId;
    }

    private function createWikiInDB($name, $userId) {
        $this->db->startAtomic( __METHOD__ );
        $this->db->insert('wiki_farm',
            [
                'fk_created_by' => $userId,
                'wiki_name' => $name,
                'wiki_status' => 'IN_CREATION'
            ]);
        $wikiId = $this->db->insertId();
        $this->db->endAtomic( __METHOD__ );
        return $wikiId;
    }

    public function updateToCreated($wikiId) {
        $this->db->startAtomic( __METHOD__ );
        $this->db->update('wiki_farm',
            [
                'wiki_status' => 'CREATED'
            ],
            [
                'id' => $wikiId
            ]
        );
        $this->db->endAtomic( __METHOD__ );
    }

    public function getAllWikisCreatedById($userId): array
    {
        $results = [];
        $res = $this->db->select('wiki_farm', ['id', 'wiki_name', 'wiki_status', 'created_at'],
            ['fk_created_by' => $userId ]);
        foreach ( $res as $row ) {
            $results[] =
            [
                'id' => $row->id,
                'wiki_name' => $row->wiki_name,
                'created_at' => $row->created_at,
                'wiki_status' => $row->wiki_status,

            ];

        }
        return $results;
    }

    public function getAllUsersOfWiki($wikiId): array
    {
        $results = [];
        $res = $this->db->select('wiki_farm_user', ['fk_user_id'], ['fk_wiki_id' => $wikiId ]);
        foreach ( $res as $row ) {
            $results[] =
                [
                    'user' => User::newFromId($row->fk_user_id)
                ];

        }
        return $results;
    }

    public function addUserToWiki(User $user, $wikiId, $status_enum) {
        $this->db->startAtomic( __METHOD__ );
        $this->db->insert('wiki_farm_user',
            [
                'fk_user_id' => $user->getId(),
                'fk_wiki_id' => $wikiId,
                'status_enum' => $status_enum
            ]);
        $this->db->endAtomic( __METHOD__ );
    }

    public function removeUserFromWiki(User $user, $wikiId) {
        $this->db->startAtomic( __METHOD__ );
        $this->db->delete('wiki_farm_user',
            [
                'fk_user_id' => $user->getId(),
                'fk_wiki_id' => $wikiId
            ]);
        $this->db->endAtomic( __METHOD__ );
    }

    public function removeWiki($wikiId) {
        $this->db->startAtomic( __METHOD__ );
        $this->db->delete('wiki_farm',
            [
                'id' => $wikiId
            ]);
        $this->db->endAtomic( __METHOD__ );
    }

    public function mayAccess($user, $wikiId): bool
    {
        $rows = $this->db->select("wiki_farm_user", ["status_enum"],
            ['fk_user_id' => $user->getId(), 'fk_wiki_id' => $wikiId]);

        $row = $rows->fetchRow();
        return ($row !== false);
    }

    public function setupTables()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS wiki_farm (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        wiki_name VARCHAR(255) NOT NULL,
                        fk_created_by INT(10) UNSIGNED NOT NULL,
                        wiki_status VARCHAR(16) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (fk_created_by) 
                        REFERENCES `user`(user_id)
                        ON UPDATE RESTRICT 
                        ON DELETE CASCADE
                    )  ENGINE=INNODB;');

        $this->db->query('CREATE TABLE IF NOT EXISTS wiki_farm_user (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        fk_user_id INT(10) UNSIGNED NOT NULL,
                        fk_wiki_id INT NOT NULL,
                        status_enum VARCHAR(10) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (fk_user_id) 
                        REFERENCES `user`(user_id)
                        ON UPDATE RESTRICT 
                        ON DELETE CASCADE,
                        FOREIGN KEY (fk_wiki_id) 
                        REFERENCES `wiki_farm`(id)
                        ON UPDATE RESTRICT 
                        ON DELETE CASCADE
                    )  ENGINE=INNODB;');

    }
}