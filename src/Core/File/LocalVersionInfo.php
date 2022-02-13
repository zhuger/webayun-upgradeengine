<?php
namespace webayun\upgradeengine\Core\File;


class LocalVersionInfo implements ObjectParser
{
	public $version;
	public $versionUpdateTime;
	public $updateTime;
	public $updateEngineVersion;
    public $updateLogs;

	public function parse($s)
	{
		$arr = json_decode($s, true);

		$this->version             = $arr['version'];
		$this->updateTime          = $arr['update_time'];
		$this->versionUpdateTime   = $arr['version_update_time'];
		$this->updateEngineVersion = $arr['update_engine_version'];
		//增加对更新日志的支持
		$this->updateLogs          = $arr['update_logs'];
	}

	public function writeString()
	{
		$arr = $this->toArray();

		return json_encode($arr);
	}

	public function toArray()
	{
		$data = array(
			'version'               => $this->version,
			'update_time'           => $this->updateTime,
			'version_update_time'   => $this->versionUpdateTime,
			'update_engine_version' => $this->updateEngineVersion,
			//增加对更新日志的支持
			'update_logs'           => $this->updateLogs,
		);

		return $data;
	}

	public function init()
	{
        $this->version             = '1.0.0';
        $this->updateTime          = '';
        $this->versionUpdateTime   = '';
        $this->updateEngineVersion = '1.0.0';
        //增加对更新日志的支持 改为当前版本对应更新日志简述 默认值为''
        $this->updateLogs          = '';
        /*
		if(class_exists('\webayun\upgradeengine\Config')){
			$this->version             = \webayun\upgradeengine\Config::$version;
			$this->updateTime          = \webayun\upgradeengine\Config::$updateTime;
			$this->versionUpdateTime   = \webayun\upgradeengine\Config::$versionUpdateTime;
			$this->updateEngineVersion = \webayun\upgradeengine\Config::$updateEngineVersion;
			//增加对更新日志的支持
			$this->updateLogs          = array();
		}else{
			$this->version             = '1.0.0';
			$this->updateTime          = '';
			$this->versionUpdateTime   = '';
			$this->updateEngineVersion = '1.0.0';
			//增加对更新日志的支持
			$this->updateLogs          = array();
		}
        */

	}
}
