<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientMock;
use MediaWiki\Rest\SimpleHandler;

class GetAvailableRGroups extends SimpleHandler
{

    public function run()
    {
        global $wgCEUseMoleculeRGroupsClientMock;
        $rGroupClient = $wgCEUseMoleculeRGroupsClientMock ? new MoleculeRGroupServiceClientMock()
            : new MoleculeRGroupServiceClientImpl();

        $rGroupsResult = array_map(function ($e) {
            return ['label' => $e, 'data' => strtolower($e)];
        }, $rGroupClient->getAvailableRGroups());

        return ['rgroups' => $rGroupsResult];
    }

    public function needsWriteAccess()
    {
        return false;
    }

}