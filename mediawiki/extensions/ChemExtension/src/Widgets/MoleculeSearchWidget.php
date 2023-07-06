<?php

namespace DIQA\ChemExtension\Widgets;

use OOUI\TextInputWidget;

class MoleculeSearchWidget extends TextInputWidget {
    public function __construct( array $config = [] ) {
        parent::__construct( $config );
    }

    protected function getJavaScriptClassName() {
        return 'OO.ui.InchiKeyLookupTextInputWidget';
    }
}