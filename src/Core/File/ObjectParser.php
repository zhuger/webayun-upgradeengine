<?php
namespace webayun\upgradeengine\Core\File;


interface ObjectParser
{
	public function parse($s);
	public function writeString();
	public function toArray();
	public function init();
}
