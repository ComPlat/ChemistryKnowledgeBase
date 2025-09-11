<?php

namespace DIQA\ChemExtension\PubChem;

use MediaWiki\MediaWikiServices;

class PubChemService {

    /**
     * Requests PubChem data if necessary and adds it into local DB. If it's already local, it's read from DB.
     *
     * @param $moleculeKey
     * @return array
     */
    public function getPubChem($moleculeKey): array
    {

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $repo = new PubChemRepository($dbr);
        $pubChemResult = $repo->getPubChemResult($moleculeKey);
        if (!is_null($pubChemResult)) {
            return $pubChemResult;
        }

        $service = new PubChemClient();
        $record = new PubChemRecordResult($service->getRecord($moleculeKey));
        $synonyms = new PubChemSynonymsResult($service->getSynonyms($moleculeKey));
        $categories = new PubChemCategoriesResult($service->getCategories($record->getCID()));

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new PubChemRepository($dbr);
        $repo->addPubChemResult($moleculeKey,
            json_encode($record->getRawResult()),
            json_encode($synonyms->getRawResult()),
            json_encode($categories->getRawResult()));
        return [
            'record' => $record,
            'synonyms' => $synonyms,
            'categories' => $categories
        ];
    }
}