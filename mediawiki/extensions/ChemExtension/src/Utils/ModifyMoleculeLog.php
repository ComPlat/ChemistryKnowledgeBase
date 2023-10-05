<?php
namespace DIQA\ChemExtension\Utils;

use Exception;
use Title;

class ModifyMoleculeLog {

    private $tmpDir;
    private $logEntries;
    private $logger;

    public function __construct()
    {
        global $wgDBname;
        global $wgCETempFolder;

        $this->tmpDir = ($wgCETempFolder ?? '/tmp') . "/modifymolecule_log/$wgDBname";

        $this->logEntries = [];
        $this->logger = new LoggerUtils('ModifyMoleculeLog', 'ChemExtension');
        $this->logger->log('ModifyMoleculeLog writes into folder: ' . $this->tmpDir);
    }

    public function addModificationLogEntry($chemformId, Title $title, $replacedChemForm, $replacedChemFormLink, $onlyMoleculeId) {
        if (!file_exists($this->tmpDir) && !mkdir($this->tmpDir, 0777, true)) {
            $this->logger->log("Can not create tmp folder: " . $this->tmpDir);
        }
        $entries = $this->logEntries[$chemformId] ?? [];
        $entries[] = [
            'title' => $title->getPrefixedText(),
            'timestamp' => time(),
            'replacedChemForm' => $replacedChemForm,
            'replacedChemFormLink' => $replacedChemFormLink,
            'onlyMoleculeId' => $onlyMoleculeId
        ];
        $this->logEntries[$chemformId] = $entries;
    }

    public function saveLog() {
        foreach($this->logEntries as $chemformId => $entries) {
            if (file_put_contents($this->tmpDir . '/' . $chemformId, json_encode($entries, JSON_PRETTY_PRINT)) === false) {
                $this->logger->log("Can not write modification log: " . $this->tmpDir . '/' . $chemformId);
            }
        }
    }

    public function getLog($chemformId) {
        $data = file_get_contents($this->tmpDir . '/' . $chemformId);
        if ($data === false) {
            return [];
        }
        $entries = json_decode($data);
        return array_map(function($e) {
            $arr = ArrayTools::propertiesToArray($e);
            $arr['title'] = Title::newFromText($arr['title']);
            return $arr;
            }, $entries);

    }
}