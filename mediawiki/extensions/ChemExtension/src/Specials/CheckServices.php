<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\TIB\TibClient;
use Philo\Blade\Blade;
use SpecialPage;
use Exception;

class CheckServices extends SpecialPage
{
    private $blade;
    private $benzolWithRGroupsMolfile;
    private $benzolMolfile;

    public function __construct()
    {
        parent::__construct('CheckServices');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);
        $this->initializeParameters();
    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        $output = $this->getOutput();
        $this->setHeaders();

        $output->addHTML($this->blade->view()->make("check-services", [
            'RGroupState' => $this->checkRGroupsService(),
            'renderState' => $this->checkRenderService(),
            'tibState' => $this->checkTIBService()])
            ->render());
    }

    private function checkRGroupsService()
    {
        try {
            $service = new MoleculeRGroupServiceClientImpl();
            $service->buildMolecules( $this->benzolWithRGroupsMolfile, [['r4' => 'ACE']]);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function checkRenderService() {
        try {
            $service = new MoleculeRendererClientImpl();
            $service->render( $this->benzolMolfile);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function checkTIBService() {
        try {
            $service = new TibClient();
            $service->suggest( "atomic", 1);
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function initializeParameters() {
        $this->benzolWithRGroupsMolfile = <<<MOL

  -INDIGO-01122317072D

  0  0  0  0  0  0  0  0  0  0  0 V3000
M  V30 BEGIN CTAB
M  V30 COUNTS 7 7 0 0 0
M  V30 BEGIN ATOM
M  V30 1 C 2.80985 -5.95007 0.0 0
M  V30 2 C 4.54015 -5.94959 0.0 0
M  V30 3 C 3.67664 -5.44997 0.0 0
M  V30 4 C 4.54015 -6.95053 0.0 0
M  V30 5 C 2.80985 -6.95502 0.0 0
M  V30 6 C 3.67882 -7.45003 0.0 0
M  V30 7 R# 5.375 -7.575 0.0 0 RGROUPS=(1 4)
M  V30 END ATOM
M  V30 BEGIN BOND
M  V30 1 2 3 1
M  V30 2 2 4 2
M  V30 3 1 1 5
M  V30 4 1 2 3
M  V30 5 2 5 6
M  V30 6 1 6 4
M  V30 7 1 7 4
M  V30 END BOND
M  V30 END CTAB
M  END
MOL;

        $this->benzolMolfile = <<<MOL

  -INDIGO-08042212082D

  0  0  0  0  0  0  0  0  0  0  0 V3000
M  V30 BEGIN CTAB
M  V30 COUNTS 6 6 0 0 0
M  V30 BEGIN ATOM
M  V30 1 C 1.25985 -4.72507 0.0 0
M  V30 2 C 2.99015 -4.72459 0.0 0
M  V30 3 C 2.12664 -4.22497 0.0 0
M  V30 4 C 2.99015 -5.72553 0.0 0
M  V30 5 C 1.25985 -5.73002 0.0 0
M  V30 6 C 2.12882 -6.22503 0.0 0
M  V30 END ATOM
M  V30 BEGIN BOND
M  V30 1 2 3 1
M  V30 2 2 4 2
M  V30 3 1 1 5
M  V30 4 1 2 3
M  V30 5 2 5 6
M  V30 6 1 6 4
M  V30 END BOND
M  V30 END CTAB
M  END

MOL;

    }
}