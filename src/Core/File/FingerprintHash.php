<?php
namespace webayun\upgradeengine\Core\File;


class FingerprintHash implements ObjectParser
{
	public $map;

	public function parse($s)
	{
		$this->map = json_decode($s, true);
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

	public function getFileHash() {
		return $this->map['files'];
	}

	public function getDBVerifyHash() {
		return $this->map['db'];
	}

	public function getVersionVerifyHash() {
		return $this->map['version'];
	}

	public function getDBExecVerifyHash() {
		return $this->map['db_exec'];
	}

	public function getExecVerifyHash() {
		return $this->map['exec'];
	}

    public function getLogVerifyHash() {
        return $this->map['log'];
    }
}
