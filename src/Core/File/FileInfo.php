<?php
namespace webayun\upgradeengine\Core\File;


class FileInfo implements ObjectParser
{
	public $filename;
	public $hash;
	public $update_time;
	public $version;
	public $url;

	private $downname;

	public function parse($arr)
	{
		//$arr = json_decode($s, true);

		$this->filename = $arr['filename'];
		$this->hash = $arr['hash'];

		$this->update_time = $arr['update_time'];
		$this->version = $arr['version'];
		$this->url = $arr['url'];
		$this->downname = $arr['downname'];

		//die;
	}

	public function writeString()
	{
		$string = json_encode($this->toArray());

		return $string;
	}

	public function toArray()
	{
		$this->downname = $this->getDownname();
		$data = array(
			'filename' => $this->filename,
			'hash' => $this->hash,
			'update_time' => $this->update_time,
			'version' => $this->version,
			'url' => $this->url,
			'downname' => $this->downname
		);

		return $data;
	}

	public function getDownname()
	{
		$name = $this->hash . '-' . substr(md5($this->filename), 0, 10);

		return $name;
	}

	public function init() {

	}
}
