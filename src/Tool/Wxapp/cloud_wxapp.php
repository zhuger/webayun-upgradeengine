<?php
namespace Webayun\UpgradeEngine\Tool;

use Webayun\UpgradeEngine\Core\Net\Net;

class cloud_wxapp{
	public $test = 'test';
	public $token;
	private $key = '';
	private $api = '';
	
	public function __construct(){
		$this->net = new Net();	
		$this->api = \Webayun\UpgradeEngine\Config::$masterHost . \Webayun\UpgradeEngine\Config::$masterMiniFix;	
	}
	public function auth()
	{
		$apphashPath = \Webayun\UpgradeEngine\Config::$dataDir . \Webayun\UpgradeEngine\Config::$apphashPathFix;
		if(file_exists($apphashPath)){
			$apphasharr = json_decode(file_get_contents($apphashPath),true);
		}else{
			$apphasharr = array();
		}
		return $this->token = _authcode($apphasharr['token'],'DECODE',$apphasharr['time']);
		
	}
	public function login()
	{

        $token = $this->auth();
		$url = $this->api . 'api/login?token='.$token;

		
		try {
			$result = $this->net->downloadText($url);
			
			$result = json_decode($result,true);

			if ($result['success'] == true) {
				$ret = array(
					'result' => true,
					'text'   => "获取登录二维码成功",
					'data'   => $result['data']
				);
				return $ret;				
			} else {
				$ret = array(
					'result' => false,
					'text'   => "获取登录二维码失败"
				);

				return $ret;
			}

		} catch (\Exception $e) {
			$ret = array(
				'result' => false,
				'text'   => $e->getMessage()
			);

			return $ret;
		}
	}
	public function checkStatus()
	{
		
        $token = $this->auth();
		$url = $this->api . 'api/checkStatus?token='.$token;

		
		try {
			$result = $this->net->downloadText($url);
			
			$result = json_decode($result,true);

			if ($result['success'] == true) {
				$ret = array(
					'result' => true,
					'text'   => "登录成功",
					'data'   => $result['data']
				);
				return $ret;				
			} else {
				$ret = array(
					'result' => false,
					'text'   => "登录失败",
					'data'   => $result['data']
				);

				return $ret;
			}

		} catch (\Exception $e) {
			$ret = array(
				'result' => false,
				'text'   => $e->getMessage()
			);

			return $ret;
		}
	}	

	public function preview($appid,$version,$desc,$siteinfo,$modules)
	{

		$url = $this->api . 'api/preview';
		
		$token = $this->auth();

		$module_name = key($modules);
		
		$params = array();
		$params['appid'] = $appid;
		$params['libVersion'] = $version;
		$params['desc'] = $desc;
		$params['siteinfo'] = $siteinfo;			
		$params['token'] = $token;			
		$params['wxapp_ident'] = $module_name;			
		$params['wxapp_version'] = $modules[$module_name]['version'];			
		//$params['wxapp_ident'] = 'wxapp';	
		try {
			$result = $this->net->ihttp_post($url,$params,0);
			$result = json_decode($result['content'], true);
			if ($result['success'] == true) {
				$ret = array(
					'result' => true,
					'text'   => "预览成功",
					'data'   => $result['data']
				);
				return $ret;				
			} else {
				if(empty($result['data'])){
					$err = "预览失败";
				}else{
					$err = $result['data'];
				}
				$ret = array(
					'result' => false,
					'text'   => $err
				);

				return $ret;
			}

		} catch (\Exception $e) {
			$ret = array(
				'result' => false,
				'text'   => $e->getMessage()
			);

			return $ret;
		}
		
	}
	
	public function upload($appid,$version,$desc,$siteinfo,$modules)
	{

		$url = $this->api . 'api/upload';
		
		$token = $this->auth();
		
		$module_name = key($modules);
		
		$params = array();
		$params['appid'] = $appid;
		$params['libVersion'] = $version;
		$params['desc'] = $desc;
		$params['siteinfo'] = $siteinfo;			
		$params['token'] = $token;			
		$params['wxapp_ident'] = $module_name;			
		$params['wxapp_version'] = $modules[$module_name]['version'];
		//$params['wxapp_ident'] = 'wxapp';
		try {
			$result = $this->net->ihttp_post($url,$params,0);
			$result = json_decode($result['content'], true);
			if ($result['success'] == true) {
				$ret = array(
					'result' => true,
					'text'   => "上传成功",
					'data'   => $result['data']
				);
				return $ret;				
			} else {
				if(empty($result['data'])){
					$err = "上传失败";
				}else{
					$err = $result['data'];
				}
				$ret = array(
					'result' => false,
					'text'   => $err
				);

				return $ret;
			}

		} catch (\Exception $e) {
			$ret = array(
				'result' => false,
				'text'   => $e->getMessage()
			);

			return $ret;
		}
		
	}
}