<?php
namespace webayun\upgradeengine\Core\File;


class TableMeta implements ObjectParser
{
	public $map;

	public function parse($s)
	{
		$arr = json_decode($s, true);

		$this->map = $arr;
	}

	public function writeString()
	{
		$string = json_encode($this->toArray());

		return $string;
	}

	public function toArray()
	{
		return $this->map;
	}


	public function init() {

	}
}
