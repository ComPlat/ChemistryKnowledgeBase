<?php

namespace DIQA\ChemExtension\Literature;

use Exception;
use MediaWiki\MediaWikiServices;

class DOIResolver
{

    public function resolve($doi)
    {
        try {
            $curl = null;

            $doi = trim($doi);
            if ($doi === '') {
                throw new Exception("DOI is empty");
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_NOBODY => false,
                CURLOPT_HTTPGET => true,
                CURLOPT_URL => "https://dx.doi.org/$doi",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.citationstyles.csl+json'
                ]
            ));

            $responseBody = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($statusCode == 404) {
                throw new Exception("DOI could not be found: $doi");
            }
            if ($statusCode != 200) {
                throw new Exception("resolving DOI failed with status code: $statusCode");
            }

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
                DB_MASTER
            );
            $repo = new LiteratureRepository($dbr);
            $repo->addLiterature($doi, $responseBody);

            return json_decode($responseBody);
        } finally {
            if (!is_null($curl)) {
                curl_close($curl);
            }
        }
    }
}