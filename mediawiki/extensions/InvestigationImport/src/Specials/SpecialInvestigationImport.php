<?php

namespace DIQA\InvestigationImport\Specials;

use Philo\Blade\Blade;
use SpecialPage;

class SpecialInvestigationImport extends SpecialPage
{
    private $blade;


    public function __construct()
    {
        parent::__construct('InvestigationImport');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

    }

    function execute($par)
    {
        $output = $this->getOutput();
        $this->setHeaders();
    }

}