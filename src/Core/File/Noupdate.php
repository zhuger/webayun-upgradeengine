<?php
namespace webayun\upgradeengine\Core\File;


class Noupdate implements ObjectParser
{
	public $paths;
	public $updateTime;

	public function parse($s) {
		$arr = json_decode($s, true);

		$this->paths = $arr['paths'];
		$this->updateTime = $arr['update_time'];
	}

	public function writeString() {
		$arr = $this->toArray();

		return json_encode($arr);
	}

	public function toArray() {
		$data = array(
			'paths'=>$this->paths,
			'update_time'=>$this->updateTime
		);

		return $data;
	}

	public function init() {
		$this->paths = '';
		$this->updateTime = '';
	}
}
