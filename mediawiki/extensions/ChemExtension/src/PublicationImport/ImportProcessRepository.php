<?php

namespace DIQA\ChemExtension\PublicationImport;

use Wikimedia\Rdbms\IMaintainableDatabase;

class ImportProcessRepository {

    public const STATUS_SCHEDULED = 'SCHEDULED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_FINISHED = 'FINISHED';
    public const STATUS_FAILED = 'FAILED';

    private $db;

    /**
     * @param IMaintainableDatabase $db
     */
    public function __construct(IMaintainableDatabase $db)
    {
        $this->db = $db;
    }

    public function setupTables()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS import_process (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            page_title VARCHAR(255) NOT NULL,
                            doi VARCHAR(255) NOT NULL,
                            status VARCHAR(20) NOT NULL DEFAULT "SCHEDULED",
                            created_at DATETIME NOT NULL,
                            started_at DATETIME NULL,
                            message TEXT NULL
                        )  ENGINE=INNODB;');
        $this->db->query('ALTER TABLE import_process ADD INDEX IF NOT EXISTS import_process_status_idx (status)');
        $this->db->query('ALTER TABLE import_process ADD INDEX IF NOT EXISTS import_process_doi_idx (doi)');

        return [ 'import_process' ];
    }

    public function dropTables()
    {
        $this->db->query('DROP TABLE IF EXISTS import_process;');

        return [ 'import_process' ];
    }

    public function addImportProcess($pageTitle, $doi): int
    {
        $this->db->insert('import_process',
            [
                'page_title' => $pageTitle,
                'doi' => $doi,
                'status' => self::STATUS_SCHEDULED,
                'created_at' => date('Y-m-d H:i:s'),
                'started_at' => null,
            ]);
        return $this->db->insertId();
    }

    public function markAsRunning(int $id): void
    {
        $this->db->update('import_process',
            [
                'status' => self::STATUS_RUNNING,
                'started_at' => date('Y-m-d H:i:s'),
            ], [
                'id' => $id,
            ]);
    }

    public function markAsFinished(int $id): void
    {
        $this->db->update('import_process',
            [
                'status' => self::STATUS_FINISHED,
            ], [
                'id' => $id,
            ]);
    }

    public function markAsFailed(int $id, string $message): void
    {
        $this->db->update('import_process',
            [
                'status' => self::STATUS_FAILED,
                'message' => $message,
            ], [
                'id' => $id,
            ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->update('import_process',
            [
                'status' => $status,
            ], [
                'id' => $id,
            ]);
    }

    public function getImportProcess(int $id)
    {
        $res = $this->db->select('import_process',
            ['id', 'page_title', 'doi', 'status', 'created_at', 'started_at', 'message'],
            ['id' => $id]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $this->rowToArray($row);
        }
        return null;
    }

    public function getImportProcessesByStatus(string $status): array
    {
        $res = $this->db->select('import_process',
            ['id', 'page_title', 'doi', 'status', 'created_at', 'started_at', 'message'],
            ['status' => $status]);
        $results = [];
        foreach ($res as $row) {
            $results[] = $this->rowToArray($row);
        }
        return $results;
    }

    public function getImportProcessByDOI(string $doi)
    {
        $res = $this->db->select('import_process',
            ['id', 'page_title', 'doi', 'status', 'created_at', 'started_at', 'message'],
            ['doi' => $doi]);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            return $this->rowToArray($row);
        }
        return null;
    }

    public function getAllImportProcesses(): array
    {
        $res = $this->db->select('import_process',
            ['id', 'page_title', 'doi', 'status', 'created_at', 'started_at', 'message'],
            [],
            __METHOD__,
            [ 'ORDER BY' => 'created_at DESC']);
        $results = [];
        foreach ($res as $row) {
            $results[] = $this->rowToArray($row);
        }
        return $results;
    }

    public function getAllImportProcessesSince(string $since): array
    {
        $res = $this->db->select('import_process',
            ['id', 'page_title', 'doi', 'status', 'created_at', 'started_at', 'message'],
            [ 'created_at >= ' . $this->db->addQuotes($since) ],
            __METHOD__,
            [ 'ORDER BY' => 'created_at DESC']);
        $results = [];
        foreach ($res as $row) {
            $results[] = $this->rowToArray($row);
        }
        return $results;
    }

    public function deleteImportProcess(int $id): void
    {
        $this->db->delete('import_process', ['id' => $id]);
    }

    private function rowToArray($row): array
    {
        return [
            'id' => (int)$row->id,
            'page_title' => $row->page_title,
            'doi' => $row->doi,
            'status' => $row->status,
            'created_at' => $row->created_at,
            'started_at' => $row->started_at,
            'message' => $row->message,
        ];
    }
}