<?php

namespace DIQA\FacetedSearch2\Utils;

class Logger {

    private const LOG_DIR = __DIR__ . '/../../logs';
    private const LOG_FILE = 'faceted-search.log';

    /**
     * Writes a message to the log file in the logs/ folder.
     *
     * @param string $message  The message to log.
     * @param string $level    Log level label, e.g. 'INFO', 'WARNING', 'ERROR'.
     */
    public static function log(string $message, string $level = 'INFO'): void {
        $logDir = self::LOG_DIR;

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        if (!is_writable($logDir)) {
            throw new \Exception("Log directory is not writable: $logDir");
        }

        $timestamp = date('Y-m-d H:i:s');
        $line = sprintf('[%s] [%s] %s' . PHP_EOL, $timestamp, strtoupper($level), $message);

        $date = date('Y-m-d');
        file_put_contents($logDir . '/' . self::LOG_FILE . '-' . $date, $line, FILE_APPEND | LOCK_EX);
    }


    public static function info(string $message): void {
        self::log($message, 'INFO');
    }

    public static function warning(string $message): void {
        self::log($message, 'WARNING');
    }

    public static function error(string $message): void {
        self::log($message, 'ERROR');
    }
}