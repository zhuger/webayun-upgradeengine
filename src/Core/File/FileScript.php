<?php
namespace webayun\upgradeengine\Core\File;


class FileScript implements ObjectParser
{
	public $filename;
	public $hash;
	public $url;
	public $upsort;

	public function parse($arr)
	{
		//$arr = json_decode($s, true);

		$this->filename = $arr['filename'];
		$this->hash = $arr['hash'];
		$this->url = $arr['url'];
		$this->upsort = $arr['upsort'];

		//die;
	}

	public function writeString()
	{
		$string = json_encode($this->toArray());

		return $string;
	}

	public function toArray()
	{
		$data = array(
			'filename' => $this->filename,
			'hash' => $this->hash,
			'url' => $this->url,
			'upsort' => $this->upsort
		);

		return $data;
	}

	public function init() {

	}
}
