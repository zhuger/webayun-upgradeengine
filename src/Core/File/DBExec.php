<?php
namespace webayun\upgradeengine\Core\File;


class DBExec implements ObjectParser
{
	public $sqlMap;

	public function parse($s)
	{
		$this->sqlMap = json_decode($s, true);
	}

	public function writeString()
	{
		$string = json_encode($this->toArray());

		return $string;
	}

	public function toArray()
	{
		return $this->sqlMap;
	}

	public function init() {
		$this->sqlMap = array();
	}
}
