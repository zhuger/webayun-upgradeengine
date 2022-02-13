<?php
namespace webayun\upgradeengine\Core\File;


class UpdateLog implements ObjectParser
{
	public $version;
	public $logNot;
	public $updateTime;
	public $logTxt;
	public function parse($arr)
	{
		//$arr = json_decode($s, true);

		$this->version             = $arr['version'];
		$this->updateTime          = $arr['update_time'];
		$this->logNot              = $arr['log_not'];
		$this->logTxt              = $arr['log_txt'];
	}

	public function writeString()
	{

	}

	public function toArray()
	{
        $data = array(
            'version'               => $this->version,
            'update_time'           => $this->updateTime,
            'log_not'               => $this->logNot,
            'log_txt'               => $this->logTxt,
        );

        return $data;
	}

	public function init()
	{

	}
}
