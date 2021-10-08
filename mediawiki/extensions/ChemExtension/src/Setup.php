<?php
namespace DIQA\ChemExtension;

class Setup {

    public static function initModules() {

        global $wgResourceModules;
        global $IP;

        $wgResourceModules['ext.diqa.chemextension'] = array(
            'localBasePath' => "$IP",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [ '/extensions/ChemExtension/scripts/ve.extend.js'],
            'styles' => [ '/extensions/ChemExtension/skins/main.css'],
            'dependencies' => ['ext.visualEditor.core'],
        );
    }

    public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {

        $out->addModules('ext.diqa.chemextension');

        self::checkPrivileges();
    }

    public static function onParserFirstCallInit( \Parser $parser ) {

        // Create a function hook associating the "example" magic word with renderExample()
        $parser->setHook( 'chemform', [ self::class, 'renderIframe' ] );
    }

    public static function renderIframe( $innerText, array $arguments, \Parser $parser, \PPFrame $frame ) {
        global $wgScriptPath;
        $path = "$wgScriptPath/extensions/ChemExtension/cheminfo";
        $output = "<iframe class=\"chemformula\" src=\"$path/index.html?formula=$innerText\" width='800px' height='400px'></iframe>";
        return array( $output, 'noparse' => true, 'isHTML' => true );
    }

    /**
     * Checks the privileges of a user.
     *
     *  - proof-of-concept, not a real implementation
     *
     * @return \User|void
     */
    private static function checkPrivileges()
    {
        global $wgUser;
        if ($wgUser->isAnon()) {
            return;
        }
        $callingurl = strtolower($_SERVER['REQUEST_URI']);
        if (strpos($callingurl, '/mediawiki') === 0) {

        } elseif (strpos($callingurl, '/wiki2') === 0) {

            if ($wgUser->getName() != "WikiSysop") {
                print "Zugriff verweigert";
                die();
            }
        }
        return $wgUser;
    }
}