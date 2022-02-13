<?php
namespace webayun\upgradeengine\Core\File;


class VersionInfo implements ObjectParser
{
	public $version;
	public $updateTime;
	public $updateEngineVersion;

	public function parse($s) {
		$arr = json_decode($s, true);

		$this->version = $arr['version'];
		$this->updateTime = $arr['update_time'];
		$this->updateEngineVersion = $arr['update_engine_version'];
		//增加对更新日志的支持
		$this->updateLogs = $arr['update_logs'];
	}

	public function writeString() {
		$arr = $this->toArray();

		return json_encode($arr);
	}

	public function toArray() {
		$data = array(
			'version'=>$this->version,
			'update_time'=>$this->updateTime
		);

		return $data;
	}

	public function init() {
		$this->version = '1.0.0';
		$this->updateTime = '';
		$this->updateEngineVersion = '1.0.0';
		//增加对更新日志的支持
		$this->updateLogs = array();
	}
}
