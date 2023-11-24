<?php
namespace DIQA\PageImport;

use DateTime;
use Exception;

/**
 * @author Michael
 *
 */
class LoggerUtils {
	private $logEntries = array();
	private $id = '';
	private $extension = '';
	private $keepMessages = 'OFF';
	private $logpath = '';
	private $globalLogpath = '';
	private $logToConsole;

	/**
	 * set $wgODBLogLevel to on of these
	 */
	const LOG_LEVELS = array (
			'OFF' =>   0,
			'TRACE' => 1,
			'DEBUG' => 2,
			'LOG' =>   3,
			'INFO' =>  3,
			'' =>      3,
			'WARN' =>  4,
			'ERROR' => 5,
			'FATAL' => 6
	);

	/**
	 * create a Logger for the extension and use the $id in each line to indicate the root
	 * for the log entry.
	 *
	 * @param String $id
	 * @param String $extension leave blank to write to the general log-file
	 * @param String $keepMessages, cf. LOG_LEVELS
     * @param bool $logToConsole if false, only write to log file
	 */
	public function __construct($id, $extension='', $keepMessages = 'OFF', $logToConsole = true) {
		global $wgWikiServerPath;

		$this->id = $id;
		$this->keepMessages = $keepMessages;
        $this->logToConsole = $logToConsole;

		$date = (new DateTime())->format('Y-m-d');

		if($extension == '') {
			$this->logpath = '';
		} else {
			$this->extension = $extension;
			$this->logpath = "$wgWikiServerPath/extensions/$extension/logFiles/{$extension}_$date.log";
			static::ensureDirExists ($this->logpath);
		}

		global $wgODBgeneralLogFile;
		$general = $wgODBgeneralLogFile ?? 'general';
		$this->globalLogpath = "$wgWikiServerPath/logs/{$general}_$date.log";
		static::ensureDirExists ($this->globalLogpath);
	}

	/**
	 * creates the directory for the given $filename, if it does not exist, and makes it writable
	 */
	 static private function ensureDirExists($filename) {
		$logdir= dirname($filename);
		if(!file_exists($logdir)) {
			mkdir($logdir);
			chmod($logdir, 0775);
		}
	}

	/**
	 * @return String the log level for this logger
	 */
	private function logLevel() {
		global $wgODBLogLevel;
		if(isset($wgODBLogLevel)) {
			return static::LOG_LEVELS[$wgODBLogLevel];
		} else {
			return static::LOG_LEVELS['LOG'];
		}
	}

	/**
	 * @return array of messages created during processing
	 */
	public function getLogMessages() {
		return $this->logEntries;
	}

	/**
	 * @return String of log messages created during processing
	 */
	public function getLogMessagesAsString() {
		$y = '';
		foreach ($this->logEntries as $log) {
			$y .= "$log\n";
		}
		return $y;
	}

	public function clearLogMessages() {
		$this->logEntries = array();
	}

	public function trace($message) {
		$this->logMessage('TRACE', $message);
	}

	public function debug($message) {
		$this->logMessage('DEBUG', $message);
	}

	public function log($message) {
		$this->logMessage('', $message);
	}

	public function warn($message) {
		$this->logMessage('WARN', $message);
	}

	public function error($message, Exception $e = null) {
		$this->logMessage('ERROR', $message);
		if($e != null) {
			$this->logMessage('ERROR', get_class($e) . "({$e->getCode()}): {$e->getMessage()}");
		}
	}

	/**
	 * @deprecated use error() instead
	 */
	public function logError($message) {
		$this->error($message);
	}

	public function fatal($message) {
		$this->logMessage('FATAL', $message);
	}

	private function logMessage($level, $message) {
		$this->keepMessage($level, $message);

		if($this->logLevel() > static::LOG_LEVELS[$level]) {
			return;
		}

		if(strlen($level) == 0) {
			$level .= '     ';
		} else if(strlen($level) == 3) {
			$level .= '  ';
		} else if(strlen($level) == 4) {
			$level .= ' ';
		}

		$pid = getmypid() . '';
		if(strlen($pid) == 0) {
			$pid .= '     ';
		} else if(strlen($pid) == 1) {
			$pid .= '    ';
		} else if(strlen($pid) == 2) {
			$pid .= '   ';
		} else if(strlen($pid) == 3) {
			$pid .= '  ';
		} else if(strlen($pid) == 4) {
			$pid .= ' ';
		}

		$fullMessage = date('H:i:s') . " $pid $level {$this->id} - $message\n";
		$this->logToFile( $fullMessage );

		$fullMessage = date('H:i:s') . " $pid $level {$this->extension}/{$this->id} - $message\n";
		if ($this->logToConsole ) {
            $this->logToConsole($fullMessage);
        }
		$this->logToGlobalFile( $fullMessage );
	}

	/**
	 * add message to $this->logEntries[] for later retrieval
	 * @param String $level
	 * @param String $message
	 */
	private function keepMessage($level, $message) {
		if(static::LOG_LEVELS[$this->keepMessages] > static::LOG_LEVELS[$level]) {
			return;
		}

		if($level == '' || $level == 'LOG'){
			$keepMessage = $message;
		} else {
			$keepMessage = "$level $message";
		}
		$this->logEntries[] = $keepMessage;
	}

	/**
	 * Log messages to log file
	 * @param String $message
	 * @return void
	 */
	private function logToFile($message) {
		if($this->logpath != '') {
			$logfile = fopen($this->logpath, 'a');
			if($logfile) {
				fwrite($logfile, $message);
				fclose($logfile);
			}
		}
	}

	/**
	 * Log messages to log file
	 * @param String $message
	 * @return void
	 */
	private function logToGlobalFile($message) {
		if($this->globalLogpath != '') {
			$logfile = fopen($this->globalLogpath, 'a');
			if($logfile) {
				fwrite($logfile, $message);
				fclose($logfile);
			}
		}
	}

	/**
	 * Log messages to console only, when in CLI-mode
	 * @param String $message
	 * @return void
	 */
	 private function logToConsole($message) {
		if ( PHP_SAPI === 'cli' && !defined('UNITTEST_MODE')) {
			echo $message;
		}
	 }

	 public function getLogPath() {
	 	return $this->logpath;
	 }

}