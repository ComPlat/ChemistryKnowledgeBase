<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\TIB\TibClient;
use eftec\bladeone\BladeOne;
use Exception;
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

        $responses= $this->doParallelCheckRequests();

        $dataToRender = array_map(fn ($e) => $e['_error'] ?? true, $responses);
        $output->addHTML($this->blade->run("check-services", [
            ...$dataToRender,
            'openAIState' => $this->checkOpenAIService(),
        ])
        );
    }

    private function checkRGroupsService()
    {
        try {
            $service = new MoleculeRGroupServiceClientImpl();
            return $service->check();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function checkRenderService()
    {
        try {
            $service = new MoleculeRendererClientImpl();
            return $service->check();

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function checkTIBService()
    {
        try {
            $service = new TibClient();
            return $service->check();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function checkOpenAIService()
    {
        $aiClient = AIClient::getAIClient();
        $result = $aiClient->ping();
        return $result['ok'] ? true : $result['message'];
    }

    /**
     * @return array
     */
    public function doParallelCheckRequests(): array
    {
        $handles = [
            'RGroupState' => $this->checkRGroupsService(),
            'renderState' => $this->checkRenderService(),
            'tibState' => $this->checkTIBService(),
        ];
        $multi = curl_multi_init();
        foreach ($handles as $i => $ch) {
            if ($ch === false) {
                continue;
            }
            curl_multi_add_handle($multi, $ch);
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
        foreach ($handles as $i => $ch) {
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