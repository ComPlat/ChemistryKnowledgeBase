<?php
namespace DIQA\ChemExtension;

use MediaWiki\MediaWikiServices;
use SpecialPage;
use Title;

class SpecialKetcherEditor extends SpecialPage {

    public function __construct() {

        parent::__construct ( 'KetcherEditor', '', true);

    }

    /**
     *
     * {@inheritDoc}
     * @see SpecialPage::execute()
     */
    public function execute($subPage) {

        $this->getOutput()->setPageTitle('Ketcher Editor');
        // create the form
        global $wgScriptPath;
        $path = "$wgScriptPath/extensions/ChemExtension/ketcher";
        $chemform = isset($_GET['chemform']) ? urlencode($_GET['chemform']) : '';
        $this->getOutput()->addHTML("<iframe id=\"ifKetcher\" src=\"$path/index.html?chemform=$chemform\" width=\"800\" height=\"600\"></iframe>");
    }


}
