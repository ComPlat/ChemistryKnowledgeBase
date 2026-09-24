<?php
namespace DIQA\WikiFarm;

use DIQA\WikiFarm\WikiGenerator\WikiGenerator;
use MediaWiki\MediaWikiServices;
use Exception;

class CreateWikiJob extends \Job {

    private WikiRepository $wikiRepository;
    private LoggerUtils $loggerUtils;

    public function __construct( $title, $params ) {
        parent::__construct( 'CreateWikiJob', $title, $params );
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->wikiRepository = new WikiRepository($dbr);
        $this->loggerUtils = new LoggerUtils('CreateWikiJob', 'WikiFarm');
    }

    public function run()
    {
        $wikiName = $this->params['wikiName'];
        $wikiId = $this->params['wikiId'];
        $wikiConfig = <<<WIKICONFIG
{
    "wikiName": "$wikiName",
    "wikiId": "wiki$wikiId",
    "useLdap": false,
    "ldap": {

    },
    "readForAll": false,
    "favicon": "/var/www/html/mediawiki/extensions/ChemExtension/resources/favicon.ico",
    "logo": "/var/www/html/mediawiki/extensions/ChemExtension/resources/home.png",
    "wordmark": "/var/www/html/mediawiki/extensions/ChemExtension/resources/home.png"
}
WIKICONFIG;

        $wikiGenerator = new WikiGenerator();
        try {
            $wikiGenerator->createNewWikiFromConfig(json_decode($wikiConfig));
            $this->wikiRepository->updateToCreated($wikiId);
            $this->loggerUtils->log("Wiki $wikiName (wiki-ID: $wikiId) created");
        } catch (Exception $e) {
            $this->loggerUtils->log("Failed to create wiki $wikiName (wiki-ID: $wikiId): " . $e->getMessage());
            $this->wikiRepository->updateToFailed($wikiId);
        }
    }


}