<?php
namespace DIQA\WikiFarm;

class CreateWikiJob extends \Job {

    public function __construct( $title, $params ) {
        parent::__construct( 'CreateWikiJob', $title, $params );
    }

    public function run()
    {
        global $IP;
        $wiki = $this->params['wiki'];
        $name = $this->params['name'];
        echo shell_exec("$IP/extensions/WikiFarm/bin/createWiki.sh $wiki $name");
    }
}