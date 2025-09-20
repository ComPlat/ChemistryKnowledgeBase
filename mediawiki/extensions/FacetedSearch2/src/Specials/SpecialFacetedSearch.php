<?php

namespace DIQA\FacetedSearch2\Specials;

use Exception;
use SpecialPage;
use OutputPage;

class SpecialFacetedSearch extends SpecialPage
{

    public function __construct()
    {
        parent::__construct('FacetedSearch2');
    }

    function execute($par)
    {
        $output = $this->getOutput();

        try {

            $this->setHeaders();
            OutputPage::setupOOUI();
            $output->addHTML('<div id="root"></div>');

        } catch (Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

}