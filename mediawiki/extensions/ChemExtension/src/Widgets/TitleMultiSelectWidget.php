<?php

namespace DIQA\ChemExtension\Widgets;

use MediaWiki\Widget\TagMultiselectWidget;

class TitleMultiSelectWidget extends TagMultiselectWidget {
    public function __construct( array $config = [] ) {
        parent::__construct( $config );
        $this->addClasses( [ 'mw-widgets-namespacesMultiselectWidget' ] );
        $this->setData([
            'namespace' => $config['namespace'] ?? null,
            'withNsPrefix' => $config['withNsPrefix'] ?? false
        ]);
    }

    protected function getJavaScriptClassName() {
        return 'OO.ui.TitleMultiSelectWidget';
    }

    public function getConfig( &$config ) {
        return parent::getConfig( $config );
    }
}