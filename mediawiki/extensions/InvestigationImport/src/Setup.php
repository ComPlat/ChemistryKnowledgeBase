<?php
namespace DIQA\InvestigationImport;

use OutputPage;
use Skin;

class Setup {

    public static function initModules() {

        global $wgResourceModules;
        global $IP;

        $baseScript = 'scripts';
        $wgResourceModules['ext.diqa.investigationImport'] = array(
            'localBasePath' => "$IP/extensions/InvestigationImport",
            'remoteExtPath' => 'InvestigationImport',
            'position' => 'bottom',
            'scripts' => [

            ],
            'styles' => [ ],
            'dependencies' => [],
        );

    }

    public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
        $out->addModules('ext.diqa.investigationImport');
    }


}