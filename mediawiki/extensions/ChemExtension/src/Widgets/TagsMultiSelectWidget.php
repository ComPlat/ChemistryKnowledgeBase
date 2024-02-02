<?php

namespace DIQA\ChemExtension\Widgets;

use MediaWiki\Widget\TagMultiselectWidget;

class TagsMultiSelectWidget extends TagMultiselectWidget {
    public function __construct( array $config = [] ) {
        parent::__construct( $config );
        $this->addClasses( [ 'mw-widgets-namespacesMultiselectWidget' ] );
    }

    protected function getJavaScriptClassName() {
        return 'OO.ui.TagsMultiSelectWidget';
    }

    public function getConfig( &$config ) {
        return parent::getConfig( $config );
    }
}