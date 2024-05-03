<?php

namespace DIQA\ChemExtension\Literature;

use DIQA\ChemExtension\Jobs\CreateAuthorPageJob;
use DIQA\ChemExtension\Jobs\LiteratureResolverJob;
use Exception;
use MediaWiki\MediaWikiServices;
use Title;

class DOIResolver
{

    /**
     * @throws Exception
     */
    public function resolve($doi)
    {

        $doi = trim($doi);
        if ($doi === '') {
            throw new Exception("DOI is empty");
        }

        list($responseBody, $statusCode) = $this->request($doi, 'https://dx.doi.org/', 'application/vnd.citationstyles.csl+json');
        if ($statusCode == 404) {
            list($responseBody, $statusCode) = $this->request($doi, 'https://api.crossref.org/works/');
            if ($statusCode == 404) {
                throw new Exception("DOI could not be found: $doi");
            } elseif ($statusCode != 200) {
                throw new Exception("resolving DOI failed with status code: $statusCode");
            }
            $result = json_decode($responseBody);
            $result = $result->message;
        } elseif ($statusCode != 200) {
            throw new Exception("resolving DOI failed with status code: $statusCode");
        } else {
            $result = json_decode($responseBody);
        }

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_MASTER
        );
        $repo = new LiteratureRepository($dbr);
        $repo->addLiterature($doi, json_encode($result));
        $this->createAuthorPageJobsAsync($result->author ?? []);

        return $result;

    }

    public function resolveAsync($doi, Title $wikiPage) {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_MASTER
        );
        $repo = new LiteratureRepository($dbr);
        $data = $repo->getLiterature($doi);
        if (!is_null($data) && $data['data'] !== '__placeholder__') {
            return;
        }
        $repo->addLiteraturePlaceholder($doi);

        $jobParams = [];
        $jobParams['doi'] = $doi;
        $job = new LiteratureResolverJob($wikiPage, $jobParams);
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $jobQueue->push($job);
    }

    private function createAuthorPageJobsAsync(array $authors)
    {
        foreach($authors as $author) {
            $name = "{$author->given} {$author->family}";
            $orcid = $author->ORCID ?? '-';
            $job = new CreateAuthorPageJob(null, ['name' => $name, 'orcid' => $orcid]);
            MediaWikiServices::getInstance()->getJobQueueGroup()->push($job);
        }
    }

    /**
     * @param string $doi
     * @return array
     */
    private function request(string $doi, string $baseUrl, string $acceptHeader = null): array
    {
        $curl = curl_init();

        $header = [];
        if (!is_null($acceptHeader)) {
            $header[] = "Accept: $acceptHeader";
        }
        curl_setopt_array($curl, array(
            CURLOPT_NOBODY => false,
            CURLOPT_HTTPGET => true,
            CURLOPT_URL => "$baseUrl$doi",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $header
        ));

        $responseBody = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (!is_null($curl)) {
            curl_close($curl);
        }
        return array($responseBody, $statusCode);
    }
}