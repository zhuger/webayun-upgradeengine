<?php
namespace webayun\upgradeengine\Core\File;

class FileManager
{
	/**
	 * @var ObjectParser
	 */
	private $parser;
	private $file;

	public function __construct($file, $parser)
	{
		$this->file = $file;
		$this->parser = $parser;
	}

	/**
	 * @param $file
	 * @param $generate Info
	 */
	public function write() {
		$content = $this->parser->writeString();
		file_put_contents($this->file, $content);
	}

	/**
	 * @param $file
	 * @param $generate Info
	 */
	public function writeList($list) {
		$content =array();
		foreach ($list as $p) {
			$content[] = $p->toArray();
		}
		$json = json_encode($content);
		file_put_contents($this->file, $json);
	}

	/**
	 * @param $key
	 *
	 * @return null|FileInfo
	 */
	public function findObject($key) {

//		if (!file_exists($this->file)) return null;
//
//		$fh = fopen($this->file, 'r');
//
//		if ($fh !== false) {
//			fgets($fh);
//			while ($s = fgets($fh)) {
//				if (mb_strpos($s,  '//' . $key . ObjectParser::SEPARATOR, 0, 'utf-8') === 0) {
//					$this->parser->parse($s);
//					return unserialize(serialize($this->parser));
//				}
//			}
//		}
//
//		return null;
	}

	/**
	 *
	 * @return ObjectParser|VersionInfo
	 */
	public function load() {
		if (!file_exists($this->file)) {

			$this->parser->init();
			return $this->parser;
		}
		$s = file_get_contents($this->file);

		$this->parser->parse($s);

		return $this->parser;
	}
    //注意 这里返回的不是对象，是数组
	public function loadList($is_array=false) {
		if (!file_exists($this->file)) return array();
		$s = json_decode(file_get_contents($this->file),true);

		$arr = array();
		foreach ($s as $v) {

			$this->parser->parse($v);
			// 使用序列化赖实现深拷贝
            if($is_array == true){
                $arr[] = unserialize(serialize($this->parser->toArray()));
            }else{
                $arr[] = unserialize(serialize($this->parser));
            }
		}

		return $arr;
	}
    //注意 这里返回的不是对象，是数组
	public function loadLists($is_array=false) {
		if (!file_exists($this->file)) return array();
		$s = json_decode(file_get_contents($this->file),true);

		$arr = array();
		foreach ($s as $k=>$v) {

			$this->parser->parse($v);
			// 使用序列化赖实现深拷贝
            if($is_array == true){
                $arr[$k] = unserialize(serialize($this->parser->toArray()));
            }else{
                $arr[$k] = unserialize(serialize($this->parser));
            }
		}

        return $arr;
	}
}
