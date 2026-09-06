<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\PublicationSearch\CrossRefAPI;
use DIQA\ChemExtension\PublicationSearch\OpenAlexAPI;
use DIQA\ChemExtension\TIB\TibClient;
use eftec\bladeone\BladeOne;
use SpecialPage;

class CheckServices extends SpecialPage
{
    private $blade;


    public function __construct()
    {
        parent::__construct('CheckServices', 'delete');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new BladeOne ($views, $cache);

    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        parent::execute($par);
        $output = $this->getOutput();
        $this->setHeaders();

        $servicesData = [
            'RGroupState' => [
                'class' => MoleculeRGroupServiceClientImpl::class,
                'contact' => 'caman.nguyenthanh (at) gmail.com',
                'name' => 'R-group service',
            ],
            'renderState' =>  [
                'class' => MoleculeRendererClientImpl::class,
                'contact' => 'pierre.tremouilhac (at) kit.edu',
                'name' => 'Molecule render service',
            ],
            'tibState' => [
                'class' => TIBClient::class,
                'contact' => 'kuehn (at) diqa.de',
                'name' => 'TIB service',
            ],
            'crossRef' => [
                'class' => CrossRefAPI::class,
                'contact' => 'kuehn (at) diqa.de',
                'name' => 'CrossRef service',
            ],
            'openAlexApi' => [
                'class' => OpenAlexAPI::class,
                'contact' => 'kuehn (at) diqa.de',
                'name' => 'OpenAlex service',
            ],
        ];

        $responses= $this->doParallelCheckRequests($servicesData);

        $dataToRender = array_map(fn ($e) => $e['_error'] ?? true, $responses);
        $output->addHTML($this->blade->run("check-services", [
            'responses' => $dataToRender,
            'servicesData' => $servicesData,
            'openAIState' => $this->checkOpenAIService(),
        ])
        );
    }

    private function checkOpenAIService()
    {
        $aiClient = AIClient::getAIClient();
        $result = $aiClient->ping();
        return $result['ok'] ? true : $result['message'];
    }


    public function doParallelCheckRequests($servicesData): array
    {

        $multi = curl_multi_init();
        $handles = [];
        foreach ($servicesData as $i => $tuple) {
            $service = new $tuple['class'];
            $handles[$i] = $service->check();
            if ($handles[$i] === false) {
                continue;
            }
            curl_multi_add_handle($multi, $handles[$i]);
        }

        // Drive the multi handle until all requests complete.
        $running = 0;
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 5.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        $responses = [];
        foreach ($servicesData as $i => $tuple) {
            $ch = $handles[$i];
            if ($ch === false) {
                continue;
            }
            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($ch);
            if ($curlErrno !== 0) {
                $responses[$i] = ['_error' => 'curl error: ' . curl_error($ch)];
            } elseif ($httpCode !== 200) {
                // Preserve the error message for logging, but leave output empty so merger skips.
                $snippet = is_string($response) ? substr($response, 0, 500) : '';
                $curlError = curl_error($ch);
                $responses[$i] = ['_error' => "HTTP $httpCode: $snippet $curlError"];
            } else {
                $responses[$i] = []; // OK
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
        return $responses;
    }


}