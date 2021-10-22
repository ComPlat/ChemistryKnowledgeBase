<?php
namespace DIQA\WikiFarm;

use DIQA\WikiFarm\CreateWikiJob;

class WikiCreator {

    public static function createWikiJob($db, $name, $user) {
        $title = \Title::newFromText( "Wiki $name/CreateWikiJob" );
        $jobParams = [ 'name' => $name ];
        $userId = \User::idFromName($user);
        if (is_null($userId)) {
            print "\nUser '$user' does not exist\n";
            exit;
        }
        $wikiId = self::createWikiInDB($db, $userId);
        $jobParams['wikiId'] = "wiki$wikiId";
        $job = new CreateWikiJob( $title, $jobParams );
        \JobQueueGroup::singleton()->push( $job );
        return $wikiId;
    }

    private static function createWikiInDB($db, $userId) {
        $db->startAtomic( __METHOD__ );
        $db->insert('wiki_farm',
            ['fk_created_by' => $userId,
            ]);
        $wikiId = $db->insertId();
        $db->endAtomic( __METHOD__ );
        return $wikiId;
    }
}