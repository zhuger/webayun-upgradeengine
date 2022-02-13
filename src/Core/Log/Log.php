<?php
namespace webayun\upgradeengine\Core\Log;

//loads()->File('FileHelper');

use webayun\upgradeengine\Core\File\FileHelper;

/**
 * Created by PhpStorm.
 * User: yonh
 * Date: 16-11-6
 * Time: 下午6:40
 *
 * version 1.0.2
 */
class Log
{
	const WARNING = "warning";
	const ERROR = "error";
	const INFO = "info";

	private $logDir;
	private $fileHelper;


	public function __construct($logDir)
	{
		$this->logDir = $logDir;
		$this->fileHelper = new FileHelper();
	}

	/**
	 * 双文件操作日志
	 *
	 * @param $title
	 * @param $src
	 * @param $dest
	 */
	public function fileLog($title, $src, $dest)
	{
		$p_title = "{title}";
		$p_src = "{src}";
		$p_dest = "{dest}";

		$p = array($p_title => $title, $p_src => $src, $p_dest => $dest);

		$log = "$p_title : $p_src => $p_dest";
		foreach ($p as $k => $v) {
			$log = str_replace($k, $v, $log);
		}

		$this->log($log);
	}

	private function getLogfile($level)
	{
		$levels = array(
			self::INFO,
			self::ERROR,
			self::WARNING
		);
		if (!in_array($level, $levels)) {
			$level = Log::INFO;
		}

		return $this->logDir . "/$level.log";
	}

	public function log($message, $level = Log::INFO)
	{
		$logfile = $this->getLogfile($level);
		$this->fileHelper->mkdirIfNotExists(dirname($logfile));
		$pre = '[' . date('Y-m-d H:i:s') . '] ';

		$record = $pre . $message . PHP_EOL;

		file_put_contents($logfile, $record, FILE_APPEND);
	}
}
