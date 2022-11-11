<?php
namespace DIQA\Extension\LimitedPageCreator;

use MediaWiki\MediaWikiServices;
use SpecialPage;
use Title;

class SpecialLimitedPageCreator extends SpecialPage {

    public function __construct() {
        parent::__construct ( 'LimitedPageCreator', '', true);
    }

    /**
     *
     * {@inheritDoc}
     * @see SpecialPage::execute()
     */
    public function execute($subPage) {
        if (isset($_POST['pagename'])) {
            // this is the result of the form
            $this->redirectToPage($_POST['namespace'], $_POST['pagename']);
            return;
        }

        $this->getOutput()->setPageTitle('Neue Seite anlegen');
        // create the form
        $html = $this->createForm($subPage);
        $this->getOutput()->addHTML($html);
    }

    private function createForm($title) {
        $userGroups = $this->getUserGroups();
        $namespaces = $this->getNamespaces($userGroups);

        $html = <<<EOT
<p style='padding-top: 2ex;'>Wählen Sie einen Namespace und einen Seitentitel für die neue Wiki-Seite aus:</p>
<form action="./Spezial:CreatePage" method="post">
<div class="row">
    <div class="col-sm-12" style='padding: 1ex;text-align: left;'>
        <select name="namespace">
            $1
        </select>
        &nbsp;
        <input type="text" tabindex="0" aria-disabled="false" name="pagename" size="50" value="" placeholder="Seitentitel eingeben" autofocus="autofocus" autocomplete="off">
        &nbsp;
        <input type="submit" value="Neue Seite anlegen" name="create_page"/>
    </div>
</div>
</form>
EOT;
        $nsDropBox = '';
        foreach($namespaces as $ns) {
            $nsDropBox .= "<option value='$ns'>$ns</option>\n";
        }
        $html = str_replace('$1', $nsDropBox, $html);
        return $html;
    }

    private function redirectToPage($namespace, $pagename) {
        if($namespace) {
            $title = Title::newFromText("$namespace:$pagename");
        } else {
            $title = Title::newFromText($pagename);
        }
        
        $this->getOutput()->redirect( $title->getEditURL() );
        return;
    }

    private function getUserGroups( ) {
        $currentUser = $this->getUser();
        if($currentUser->isAnon()) {
            return [];
        }

        $currentUserGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $currentUser, 0 );
        $currentUserGroups[] = 'user'; // logged-in user
        return $currentUserGroups;
    }

    
    private function getNamespaces( $groups=[] ) {
        global $wgEditorGroupNamespaces;
        /**
         * $wgEditorGroupNamespaces['user'] = ['Produkt', 'ODB', 'Test'];
         * $wgEditorGroupNamespaces['sysop'] = ['Attribut', 'Kategorie', 'Test'];
         */
        if(isset($wgEditorGroupNamespaces['*'])) {
            $namespaces = $wgEditorGroupNamespaces['*'];
        } else {
            $namespaces = [];
        }
        foreach($groups as $group) {
            if(isset($wgEditorGroupNamespaces[$group])) {
                $namespaces = array_merge($namespaces, $wgEditorGroupNamespaces[$group]);
            }
        }
        $namespaces = array_unique($namespaces);
        sort($namespaces);
        return $namespaces;
    }

    public static function setup($dir = '../../..') {
        return;
        global $wgSpecialPages;
        global $wgResourceModules;
        global $wgAPIModules;

        $wgSpecialPages['MarkAsDeleted'] = 'ODB\Core\MarkAsDeleted\MarkAsDeleted';

        $wgResourceModules['ext.odbcore.markasdeleted'] = array(
            'localBasePath' => $dir,
            'remoteExtPath' => 'ODBcore',
            'position' => 'bottom',
            'scripts' => 'scripts/markasdeleted.js',
            'dependencies' => [],
        );

        $wgAPIModules['odbmarkasdeleted'] = 'ODB\Core\MarkAsDeleted\MarkAsDeletedAjaxAPI';
    }
}
