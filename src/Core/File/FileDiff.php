<?php
namespace webayun\upgradeengine\Core\File;


class FileDiff implements ObjectParser
{
	public $filename;//	更新文件名
	public $filename_local;//	本地文件名
	public $hash;//	更新文件哈希
	public $hash_local;//	本地文件哈希
	public $update_time;//	更新文件最后更新时间
	public $update_time_local;//	本地文件最后修改时间
	public $update_type;//	修改类型 update:更新,new新文件
	public $download_name;
	public $download_url;


	public function parse($s)
	{
		$s   = trim(substr($s, 2));
		$arr = explode(ObjectParser::SEPARATOR, $s);

		$this->filename          = $arr[0];
		$this->filename_local    = $arr[1];//	本地文件名
		$this->hash              = $arr[2];//	更新文件哈希
		$this->hash_local        = $arr[3];//	本地文件哈希
		$this->update_time       = $arr[4];//	更新文件最后更新时间
		$this->update_time_local = $arr[5];//	本地文件最后修改时间
		$this->update_type       = $arr[6];//	修改类型 update:更新,new新文件
		$this->download_name     = $arr[7];
		$this->download_url      = $arr[8];
	}

	public function writeString()
	{
		$data = $this->toArray();
		$data = json_encode($data);

		return $data;
	}

	public function toArray()
	{
		$data = array(
			'filename'          => $this->filename,
			'filename_local'    => $this->filename_local,
			'hash'              => $this->hash,
			'hash_local'        => $this->hash_local,
			'update_time'       => $this->update_time,
			'update_time_local' => $this->update_time_local,
			'update_type'       => $this->update_type,
			'download_name'     => $this->download_name,
			'download_url'      => $this->download_url,
		);

		return $data;
	}

	public function init() {

	}
}
