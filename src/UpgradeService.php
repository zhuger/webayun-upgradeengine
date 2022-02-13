<?php
namespace webayun\upgradeengine;

use webayun\upgradeengine\Core\File\DBExec;
use webayun\upgradeengine\Core\File\FileDiff;
use webayun\upgradeengine\Core\File\FileHelper;
use webayun\upgradeengine\Core\File\FileInfo;
use webayun\upgradeengine\Core\File\FileScript;
use webayun\upgradeengine\Core\File\FileManager;
use webayun\upgradeengine\Core\File\FingerprintHash;
use webayun\upgradeengine\Core\File\LocalVersionInfo;
//use webayun\upgradeengine\Core\File\ObjectParser;
use webayun\upgradeengine\Core\File\TableMeta;
use webayun\upgradeengine\Core\File\VersionInfo;
use webayun\upgradeengine\Core\File\Noupdate;
use webayun\upgradeengine\Core\File\UpdateLog;
use webayun\upgradeengine\Core\Sql\SchemaDiff;
use webayun\upgradeengine\Core\Sql\SchemaMetaInfoHelper;
use webayun\upgradeengine\Core\Sql\SqlStatementGenerate;
use webayun\upgradeengine\Core\Net\Net;
use webayun\upgradeengine\Core\Log\Log;

session_start();
class UpgradeService
{

	private $dbConfig;

	private $masterHost;
	private $agengid;
	private $agentPassword;
	private $agentDomain;

	//private $historyPath;
	private $localFileInfoPath;
	private $fileHashPath;
	private $dbHashPath;
	private $localInfoPath;
	private $versionHashPath;
	private $dbExecHashPath;
	private $execHashPath;
	private $fingerprintHashPath;
    private $updateLogHashPath;

	//private $templates;
	private $masterUrl;
	private $agentUrl;

	private $reportUrl;
	private $connectUrl;
	private $registerUrl;
	private $alertipsUrl;

	private $fingerprintPath;
	private $hashUrl;
	private $dbHashUrl;
	private $fingerprintHashUrl;
	private $versionHashUrl;
	private $dbExecHashUrl;
	private $execHashUrl;
    private $updateLogHashUrl;

	private $apphashPath;

	private $dest;
	private $downloadDir;
	private $backupDir;
	private $tempDir;

	private $version;
	private $updateTime;
	private $versionUpdateTime;
	private $updateEngineVersion;

	private $fileHelper;
	private $log;
	private $net;
	//开启日志记录
    private $islog = 0;

	private $sqlStatementGenerate;
	public $noupdates = array();
	//public $regTime;
	public $token;

	public $access_token;

    //通讯token缓存名称
	public $access_token_name = "webayun_access";
    //通讯token缓存到期时间
	public $cache_time = "600";
    //当前系统判断
    private $appName = '';
    //客户端提交数据
    public $apphash = array(
        'host' => '',
        'token' => '',
        'time' => '',
        'domain' => '',
        'domains' => '',
        'ip' => '',
        'ips' => '',
        'email' => '',
        'is_https' => '',
        'appuid' => '',
        'appid' => '',
        'appname' => ''
    );

    public function __construct($configs, $appRootDir, $masterHost, $tempDir, $dbConfig, $agentDomain, $agentid, $agentPassword)
    {

		$this->masterHost    = $masterHost;
		$this->agentId       = $agentid;
		$this->agentDomain   = $agentDomain;
		$this->agentPassword = $agentPassword;

		$this->apphash['appid'] = $configs['appId'];
		$this->apphash['appname'] = $configs['appName'];

        //判断系统授权信息文件
		if(file_exists($tempDir . $configs['apphashPathFix'])){
			$apphasharr = json_decode(file_get_contents($tempDir . $configs['apphashPathFix']),true);
            $this->apphash['token'] = weba_authcode($apphasharr['token'],'DECODE',$apphasharr['time']);
            $this->apphash['host']= $apphasharr['domain'];
            $this->apphash['time'] = $apphasharr['time'];
            $this->apphash['domain']= $apphasharr['domain'];
            $this->apphash['ip'] = $apphasharr['ip'];
            $this->apphash['email'] = $apphasharr['email'];
            $this->apphash['is_https'] = $apphasharr['is_https'];
            //$this->apphash['appuid'] = $apphasharr['appuid'];
            $this->apphash['appuid'] = $apphasharr['appuid'];
		}else{
		    //设置host domain ip is_https默认值
            if(empty($this->apphash['host'])){
                $this->apphash['host'] = $_SERVER['HTTP_HOST'];
            }
            if(empty($this->apphash['domain'])){
                $this->apphash['domain'] = $_SERVER['HTTP_HOST'];
            }
            if(empty($this->apphash['ip'])){
                $this->apphash['ip'] = $_SERVER['SERVER_ADDR'];
            }
            $this->apphash['is_https'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? '1' : '0';

        }

		$this->dest      = $appRootDir;
		$this->tempDir   = $tempDir;

		$this->downloadDir = $this->tempDir . $configs['downloadDirFix'];
		$this->backupDir   = $this->tempDir . $configs['backupDirFix'];
		$this->zipDir   = $this->tempDir . $configs['zipDirFix'];
		$this->dbConfig    = $dbConfig;

		$this->masterUrl          = $configs['masterUrlFix'] . $this->masterHost . $configs['masterApiFix'];
		$this->agentUrl          = $configs['agentUrlFix'] . $this->agentDomain . $configs['agentApiFix'];

		//更新服务器相关请求链接
		$this->hashUrl            = $this->agentUrl . $configs['hashUrlFix'];
		$this->dbHashUrl          = $this->agentUrl . $configs['dbHashUrlFix'];
		$this->dbExecHashUrl      = $this->agentUrl . $configs['dbExecHashUrlFix'];
		$this->execHashUrl        = $this->agentUrl . $configs['execHashUrlFix'];
		$this->versionHashUrl     = $this->agentUrl . $configs['versionHashUrlFix'];
		$this->fingerprintHashUrl = $this->agentUrl . $configs['fingerprintHashUrlFix'];
        $this->updateLogHashUrl   = $this->agentUrl . $configs['updateLogHashUrlFix'];
        //授权服务器相关请求链接
		$this->reportUrl 	      = $this->masterUrl . $configs['reportUrlFix'];
		$this->connectUrl         = $this->masterUrl . $configs['connectUrlFix'];
		$this->registerUrl        = $this->masterUrl . $configs['registerUrlFix'];
		$this->checkUrl        	  = $this->masterUrl . $configs['checkUrlFix'];
		$this->checkModuleUrl     = $this->masterUrl . $configs['checkModuleUrlFix'];
        $this->updateLogUrl       = $this->masterUrl . $configs['updateLogUrlFix'];
		$this->alertipsUrl        = $this->masterUrl . $configs['alertipsUrlFix'];

        //本地信息储存相关文件
		$this->localFileInfoPath  = $this->tempDir . $configs['localFileInfoPathFix'];
		$this->localInfoPath      = $this->tempDir . $configs['localInfoPathFix'];
		$this->modulesPath        = $this->tempDir . $configs['ModulesPathFix'];
		$this->noupdateInfoPath   = $this->tempDir . $configs['noupdateInfoPathFix'];

		$this->apphashPath = $this->tempDir . $configs['apphashPathFix'];

		//$this->historyPath         = $this->tempDir . $configs['historyPathFix'];
		$this->fingerprintPath     = $this->tempDir . $configs['fingerprintPathFix'];
		$this->filescriptPath      = $this->tempDir . $configs['filescriptPathFix'];
		$this->fileHashPath        = $this->fingerprintPath . $configs['fileHashPathFix'];
		$this->dbHashPath          = $this->fingerprintPath . $configs['dbHashPathFix'];
		$this->dbExecHashPath      = $this->fingerprintPath . $configs['dbExecHashPathFix'];
		$this->execHashPath        = $this->fingerprintPath . $configs['execHashPathFix'];
		$this->versionHashPath     = $this->fingerprintPath . $configs['versionHashPathFix'];
		$this->fingerprintHashPath = $this->fingerprintPath . $configs['fingerprintHashPathFix'];
        $this->updateLogHashPath   = $this->fingerprintPath . $configs['updateLogHashPathFix'];

		$this->version                = $configs['version'];
		$this->updateTime             = $configs['updateTime'];
		$this->versionUpdateTime      = $configs['versionUpdateTime'];
		$this->updateEngineVersion    = $configs['updateEngineVersion'];

		$this->net                  = new Net();
		$this->fileHelper           = new FileHelper();
		$this->log                  = new Log($this->tempDir . '/log');
		$this->sqlStatementGenerate = new SqlStatementGenerate();

        //判断系统授权接口文件
        $this->appName = $configs['appName'];

        if($this->appName=="weengine"){
            $dirName = "data";
        }elseif($this->appName=="weshop"){
            $dirName = "config";
        }else{
            $dirName = "config";
        }
        $oldUpgradeFile = __DIR__ . "/config.php";
        $newUpgradeFile = __DIR__ . "../../../../../" . $dirName . "/upgrade.php";
        if (!$this->fileHelper->file_exists($newUpgradeFile)) {
            return $this->fileHelper->copyFile($oldUpgradeFile, $newUpgradeFile);
        }

	}

	public function preInstallCheck()
	{
		$ret = array('result' => true, 'text' => '检测成功');

		$tmpdirWriteable = $this->fileHelper->writeable($this->tempDir);
		if (!$tmpdirWriteable) {
			$ret = array(
				'text'   => '目录[' . $this->tempDir . ']不可写,请授权后再试',
				'result' => false
			);
		}

		$fingerprintPathExists = $this->fileHelper->file_exists($this->fingerprintPath);
		if ($fingerprintPathExists) {
			$ret = array(
				'text'   => '目录[' . $this->fingerprintPath . ']已存在,请删除后再试',
				'result' => false
			);
		}

		return $ret;
	}

	private function replaceFiles($diff)
	{
        $err = [];
		foreach ($diff as $hash) {
			if(array_key_exists($hash->filename,$this->noupdates)){
				continue;
			}
			$old = $this->getDownpath($hash);
			$new = $this->getDestpath($hash);
			$this->fileHelper->mkdirIfNotExists(dirname($new));
			if($this->islog == 1){
				$this->log->fileLog('替换文件', $old, $new);
			}

			if (!rename($old, $new)) {
				if (!@copy($old, $new)) {
					if($this->islog == 1){
						$errors= error_get_last();
						$copy_error = "复制文件失败:" . $old . " to " . $new . PHP_EOL;
						$copy_error .= "错误类型: ".$errors['type'] . PHP_EOL;
						$copy_error .= "错误信息: " . $errors['message'];
						$this->log->log($copy_error, LOG::ERROR);
					}
				}
			}

			$verify = $this->hashVerify($new, $hash->hash);

			if (!$verify) {
				if($this->islog == 1){
					$this->log->log('文件校验失败:' . $hash->filename);
				}
				$err[] = $hash->filename;
			}
		}

		return $err;
	}

	private function deleteFile($file)
	{
		if (is_dir($file)) {
			if($this->islog == 1){
				$this->log->log('删除目录:' . $file);
			}
			$flag = $this->fileHelper->deleteFolder($file);

			if (!$flag) {
				if($this->islog == 1){
					$this->log->log('删除目录失败:' . $file, Log::ERROR);
				}
			}
		} else if (is_file($file)) {
			if($this->islog == 1){
				$this->log->log('删除文件:' . $file);
			}
			$flag = $this->fileHelper->deleteFile($file);
			if (!$flag) {
				if($this->islog == 1){
					$this->log->log('删除文件失败:' . $file, Log::ERROR);
				}
			}
		}
	}

	public function writeVersionInfo()
	{
		$version     = $this->getLasterVersion();//获取temp目录版本信息

		//$versionInfo = new LocalVersionInfo();
		$versionInfo = $this->getLocalVersionInfo();
        //上报版本
		$this->report($version->version);

		$versionInfo->version                               = $version->version;
		$versionInfo->versionUpdateTime                     = $version->updateTime;
		$versionInfo->updateEngineVersion                   = $version->updateEngineVersion;
		$versionInfo->updateTime                            = date('Y-m-d H:i:s');
		//增加对更新日志的支持
        //改为仅记录当前版本更新简述
		//$versionInfo->updateLogs[$version->version]         = $version->updateLogs;
        $versionInfo->updateLogs                            = $version->updateLogs;

		$this->fileHelper->mkdirIfNotExists(dirname($this->localInfoPath));
		$fm = new FileManager($this->localInfoPath, $versionInfo);
		$fm->write();


	}

    /**
     * 清理临时文件
     * @param $deleteRuntime bool 是否删除runtime目录
     */
	public function clearTempFiles($deleteRuntime = true)
	{
		// 删除下载文件
		$this->deleteFile($this->downloadDir);
		// 删除临时目录
		$this->deleteFile($this->tempDir . '/temp');
		// 删除执行文件目录
		$this->deleteFile($this->tempDir . '/exec');

		//删除runtime目录
        if ($deleteRuntime) {
            $this->deleteFile($this->dest . "/app/Runtime");
        }
	}
    /**
     * 清理临时文件
     * @param $deleteRuntime bool 是否删除runtime目录
     */
	public function _clearTempFiles($deleteRuntime = true)
	{
		// 删除下载文件
		//$this->deleteFile($this->downloadDir);
		// 删除临时目录
		$this->deleteFile($this->tempDir . '/temp');
		// 删除执行文件目录
		$this->deleteFile($this->tempDir . '/exec');

		//删除runtime目录
        //if ($deleteRuntime) {
        //    $this->deleteFile($this->dest . "/app/Runtime");
        //}
	}
	private function checkWriteableByDiff($diff)
	{
		$err = array();

		foreach ($diff as $hash) {
			$path = $this->getDestpath($hash);

			$checkdir = dirname($path);
			$this->fileHelper->mkdirIfNotExists($checkdir);
			if (!$this->fileHelper->writeable($checkdir)) {
				$err[] = $checkdir;
				if($this->islog == 1){
					$this->log->log('目录不可写:' . $checkdir, Log::ERROR);
				}
			}
		}

		return array_unique($err);
	}

	public function checkContextWriteable() {
		if (!$this->fileHelper->writeable($this->tempDir)) {
			return $this->tempDir;
		}

		return true;
	}

	private function backupFileByDiff($diff, $version_local='')
	{
		if(empty($version_local)){
			$version_local = $this->version;
		}
		$time = "/" .$version_local .'_'. date('Ymd_His_') . strtoupper(substr(md5(rand(0, 100)), 0, 10));

		foreach ($diff as $hash) {
			$path = $this->getDestpath($hash);
			if ($this->fileHelper->file_exists($path)) {
				$len        = strlen($this->dest);
				$relpath    = substr($path, $len);
				$backuppath = $this->backupDir . $time . $relpath;
				$this->fileHelper->mkdirIfNotExists(dirname($backuppath));
				if($this->islog == 1){
					$this->log->fileLog('备份文件', $path, $backuppath);
				}
				copy($path, $backuppath);
			}
		}

		return $this->backupDir . $time;
	}

	private function getUpdateFileLogByDiff($diff)
	{
		$updateLog = "<pre>";
		foreach ($diff as $hash) {
			$updateLog .= $hash['filename'] . PHP_EOL;
		}
		$updateLog .= "</pre>";

		return $updateLog;
	}

	private function errorResult($message)
	{
		return array('result' => false, 'text' => $message);
	}

	private function successResult($message)
	{
		return array('result' => true, 'text' => $message);
	}

	private function hashVerify($file, $hash)
	{
		$fileHash = $this->fileHash($file);
		return $fileHash === $hash;
	}

	private function fileHash($filename)
	{
		if ($this->fileHelper->file_exists($filename)) {
			return md5_file($filename);
		} else {
			return "";
		}
	}

	private function getDestpath($hash)
	{
		$downname = $hash->filename;// $hash['filename'];
		$path     = $this->dest . $downname;

		return $path;
	}

	private function getDownpath($hash)
	{
		$downname = $hash->download_name;
		$path     = $this->downloadDir . "/" . $downname;

		return $path;
	}

	function hash2arr($filename)
	{
		$content = file_get_contents($filename);

		return json_decode($content, true);
	}

	function startwith($startWith, $haystack)
	{
		return mb_strpos($haystack, $startWith, 0, 'utf-8') === 0;
	}

	function endWith($endWith, $haystack)
	{
		$length = mb_strlen($endWith, 'utf-8');
		if ($length == 0) {
			return true;
		}

		return mb_substr($haystack, -$length, $length, 'utf-8') === $endWith;
	}

	function update_diff_hash_version($hashArray, $basedir)
	{
		$arr = array();

		$noupdates = $this->getnoupdate('arr');

		$noupdatesarr =array();
		$key = 0;

		foreach ($hashArray as $hash) {

			$hashfilename = $hash->filename;
			$destfilename = $basedir . "/" . $hashfilename;
			//二开增加download文件夹里的文件与远程服务器文件对比，避免很多文件需要下载的时候卡死后，刷新页面还得从头下载的情况
			//$downloadfilename = $this->downloadDir . "/" . $hash->downname;

			// 判断该文件是否存在,判断该文件哈希
			if ($this->fileHelper->file_exists($destfilename)) {
				$dest_hash = $this->fileHash($destfilename);
				$src_hash  = $hash->hash;
				if ($dest_hash != $src_hash) {

					foreach ($noupdates as $path) {
						$path = $this->dest.'/'.$path;
						if(strpos($destfilename,$path) === 0){
							$this->noupdates[$hash->filename] = $hash->filename;
						}
					}
					$arr[$key] = $this->fileInfo2FileDiff($hash);
					//二开增加download文件夹里的文件与远程服务器文件对比，避免很多文件需要下载的时候卡死后，刷新页面还得从头下载的情况
					$downloadfilename = $this->downloadDir . "/" . $arr[$key]->download_name;
					if(file_exists($downloadfilename) && $this->fileHash($downloadfilename) == $hash->hash){
						$arr[$key]->isdownload = 1;
						$arr[$key]->fileHash = $this->fileHash($downloadfilename);
					}else{
						$arr[$key]->isdownload = 0;
						$arr[$key]->fileHash = $this->fileHash($downloadfilename);
					}
					$key ++;
				}
			} else {
				//不存在直接添加更新列表
				$arr[$key] = $this->fileInfo2FileDiff($hash);
				//二开增加download文件夹里的文件与远程服务器文件对比，避免很多文件需要下载的时候卡死后，刷新页面还得从头下载的情况
				$downloadfilename = $this->downloadDir . "/" . $arr[$key]->download_name;
				if(file_exists($downloadfilename) && $this->fileHash($downloadfilename) == $hash->hash){
					$arr[$key]->isdownload = 1;
					$arr[$key]->fileHash = $this->fileHash($downloadfilename);
				}else{
					$arr[$key]->isdownload = 0;
					$arr[$key]->fileHash = $this->fileHash($downloadfilename);
				}
				$key ++;

			}
		}

		return $arr;
	}


	public function isLasterVersion()
	{
//		$local = $this->getLocalVersionInfo();
//		$new   = $this->getLasterVersion();
//		return $local->version === $new->version;

		//$this->log->log('start:' . time(), Log::WARNING);
		$diffmap = $this->getDiffMap();

		//$this->log->log('end:' . time(), Log::WARNING);

		return count($diffmap) < 1;
	}

	public function getLasterVersion()
	{
		$vi = new VersionInfo();

		$fin = $this->getFingerprintHash(true);
        if ($fin != false) {
            $verifyed = $this->hashVerify($this->versionHashPath, $fin->getVersionVerifyHash());
        } else {
            $verifyed = false;
        }


        if (!$verifyed) {
			//$this->downloadFingerprint();
            //改为指定hash文件下载，减少开销
            $this->downloadFileHashFingerprint(null,'version');
            $fin = $this->getFingerprintHash();

            if ($fin !== false) {
                $verifyed = $this->hashVerify($this->versionHashPath, $fin->getVersionVerifyHash());
            } else {
                $verifyed = false;
            }

			if (!$verifyed) {
				return false;
			}
		}

		$fm = new FileManager($this->versionHashPath, $vi);
		$vi = $fm->load();

		return $vi;
	}

	public function getLocalVersionInfo()
	{
		$file = $this->localInfoPath;

		$info = new LocalVersionInfo();

		$fm = new FileManager($file, $info);

		$info = $fm->load();

		return $info;
	}

	public function getRemoteVersion()
    {

    }
    //获取版本更新日志
    public function getLogVersion()
    {
        $vi = new UpdateLog();
        $fin = $this->getFingerprintHash(true);
        if ($fin != false) {
            $verifyed = $this->hashVerify($this->updateLogHashPath, $fin->getLogVerifyHash());
        } else {
            $verifyed = false;
        }
        if (!$verifyed) {
            //$this->downloadFingerprint();
            //改为指定hash文件下载，减少开销
            $this->downloadFileHashFingerprint(null,'log');
            $fin = $this->getFingerprintHash();
            if ($fin !== false) {
                $verifyed = $this->hashVerify($this->updateLogHashPath, $fin->getLogVerifyHash());
            } else {
                $verifyed = false;
            }
            if (!$verifyed) {
                return false;
            }
        }
        $fm = new FileManager($this->updateLogHashPath, $vi);
        $vi = $fm->loadLists(true);

        return $vi;
    }

	public function connect()
	{
		$url = $this->connectUrl;

		/* $ret = array(
			'result' => false,
			'text'   => $url . $this->conParams()
		);
		return $ret; */

		try {
			$result = $this->net->downloadText($url . $this->conParams());
			$arr    = json_decode($result, true);

			if ($arr['result'] === true) {
				//return true;
				if(!empty($arr['data']['access_token']) && !empty($arr['data']['api_url'])){
					$this->set_access_token($arr['data']['access_token'],$arr['data']['expiretime'],$arr['data']['api_url']);
					$ret = array(
						'result' => true,
						'text'   => $arr['text'],
						'data'   => $arr['data']
					);
				}else{
					$ret = array(
						'result' => false,
						'text'   => $arr['text']
					);
				}

				return $ret;
			} else {
				$ret = array(
					'result' => false,
					'text'   => $arr['text']
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


	public function set_access_token($access_token,$expiretime=600,$api_url)
	{
		$session_data = array();
		$session_data['data'] = $access_token;
		$session_data['expire'] = time()+$expiretime;
		$session_data['api_url'] = $api_url;
		$name = $this->access_token_name;
        if($this->appName=="weengine"){
            $_SESSION[$name] = $session_data;
        }elseif($this->appName=="weshop"){
            session($name,$session_data);
        }else{
            $_SESSION[$name] = $session_data;
        }

	}

	public function get_access_token()
	{
		$name = $this->access_token_name;
        if($this->appName=="weengine"){
            $access = $_SESSION[$name];
        }elseif($this->appName=="weshop"){
            $access = session($this->access_token_name);
        }else{
            $access = $_SESSION[$name];
        }
		if(!empty($access)){
			if($access['expire']>time()){
				return $access;
			}else{
				$this->clear_access_token($name);
			}
		}
		return false;
	}
	public function clear_access_token($name)
	{
        if($this->appName=="weengine"){
            unset($_SESSION[$name]);
        }elseif($this->appName=="weshop"){
            session($name,null);
        }else{
            unset($_SESSION[$name]);
        }

	}


	private function getFingerprintHash($refresh = false)
	{
		if (!$this->fileHelper->file_exists($this->fingerprintHashPath) || $refresh === true) {
			$downurl = $this->fingerprintHashUrl . $this->authParams();
			if($this->islog == 1){
				$this->log->log('下载指纹文件' . $downurl);
			}
			$this->fileHelper->mkdirIfNotExists(dirname($this->fingerprintHashPath));
			$this->net->downloadFile($downurl, $this->fingerprintHashPath);
		}

		if ($this->fileHelper->file_exists($this->fingerprintHashPath)) {
			$fm  = new FileManager($this->fingerprintHashPath, new FingerprintHash());
			$fin = $fm->load();
			if (false !== $fin && (!isset($fin->map['errno']) || $fin->map['errno'] != 1)) {
            //if (false !== $fin) {
				return $fin;
			} else {
				if($this->islog == 1){
					$this->log->log('指纹文件解析异常', Log::ERROR);
				}
			}
		} else {
			if($this->islog == 1){
				$this->log->log('指纹文件下载失败', Log::ERROR);
			}
		}

		return false;
	}

    /*
    public function downloadFileHashFingerprint($fingerprintHash = null)
    {
        try {

            if (is_null($fingerprintHash)) {
                $verify = $this->getFingerprintHash();
            } else {
                $verify = $fingerprintHash;
            }

            if ($verify === false) {
                return false;
            }

            $ret1 = $this->downloadAndVerify($this->fileHashPath, $this->hashUrl . $this->authParams(), $verify->getFileHash());

            if ($ret1 !== true) {
                return "文件指纹下载失败";
            }

            return true;
        } catch (\Exception $e) {
			if($this->islog == 1){
				$this->log->log($e->getMessage(), Log::ERROR);
			}
            return $e->getMessage();
        }
    }
    */
    //改为指定hash文件下载，减少开销
    public function downloadFileHashFingerprint($fingerprintHash = null,$hashName = null)
    {
        try {

            if (is_null($fingerprintHash)) {
                $verify = $this->getFingerprintHash();
            } else {
                $verify = $fingerprintHash;
            }

            if ($verify === false) {
                return false;
            }

            if($hashName == "file"){
                $ret1 = $this->downloadAndVerify($this->fileHashPath, $this->hashUrl . $this->authParams(), $verify->getFileHash());
            }else if($hashName == "db"){
                $ret1 = $this->downloadAndVerify($this->dbHashPath, $this->dbHashUrl . $this->authParams(), $verify->getDBVerifyHash());
            }else if($hashName == "version"){
                $ret1 = $this->downloadAndVerify($this->versionHashPath, $this->versionHashUrl . $this->authParams(), $verify->getVersionVerifyHash());
            }else if($hashName == "db_exec"){
                $ret1 = $this->downloadAndVerify($this->dbExecHashPath, $this->dbExecHashUrl . $this->authParams(), $verify->getDBExecVerifyHash());
            }else if($hashName == "exec"){
                $ret1 = $this->downloadAndVerify($this->execHashPath, $this->execHashUrl . $this->authParams(), $verify->getExecVerifyHash());
            }elseif($hashName == "log"){
                $ret1 = $this->downloadAndVerify($this->updateLogHashPath, $this->updateLogHashUrl . $this->authParams(), $verify->getLogVerifyHash());
            }else{
                $ret1 = $this->downloadAndVerify($this->fileHashPath, $this->hashUrl . $this->authParams(), $verify->getFileHash());
            }

            if ($ret1 !== true) {
                return "文件指纹下载失败";
            }

            return true;
        } catch (\Exception $e) {
            if($this->islog == 1){
                $this->log->log($e->getMessage(), Log::ERROR);
            }
            return $e->getMessage();
        }
    }

	public function downloadFingerprint($fingerprintHash = null)
	{

 		try {
			// 获取指纹信息
			if (is_null($fingerprintHash)) {
				$verify = $this->getFingerprintHash();
			} else {
				$verify = $fingerprintHash;
			}

			if ($verify === false) {
				return false;
			}

			//下载文件指纹
			$ret1 = $this->downloadAndVerify($this->fileHashPath, $this->hashUrl . $this->authParams(), $verify->getFileHash());
			//下载数据库结构指纹
			$ret2 = $this->downloadAndVerify($this->dbHashPath, $this->dbHashUrl . $this->authParams(), $verify->getDBVerifyHash());
			//版本信息
			$ret3 = $this->downloadAndVerify($this->versionHashPath, $this->versionHashUrl . $this->authParams(), $verify->getVersionVerifyHash());
			//下载执行sql指纹
			$ret4 = $this->downloadAndVerify($this->dbExecHashPath, $this->dbExecHashUrl . $this->authParams(), $verify->getDBExecVerifyHash());
			//下载执行程序指纹
			$ret5 = $this->downloadAndVerify($this->execHashPath, $this->execHashUrl . $this->authParams(), $verify->getExecVerifyHash());
            //下载更新日志文件
            $ret6 = $this->downloadAndVerify($this->updateLogHashPath, $this->updateLogHashUrl . $this->authParams(), $verify->getLogVerifyHash());
			if ($ret1 !== true) {
				return "文件指纹下载失败";
			} else if ($ret2 !== true) {
				return "数据库结构指纹下载";
			} else if ($ret3 !== true) {
				return "版本信息指纹下载失败";
			} else if ($ret4 !== true) {
				return "数据库执行语句指纹下载";
			} else if ($ret5 !== true) {
				return "执行程序指纹下载";
			} else if ($ret6 !== true) {
                return "更新日志指纹下载";
            }
			return true;
		} catch (\Exception $e) {
			if($this->islog == 1){
				$this->log->log($e->getMessage(), Log::ERROR);
			}
			return $e->getMessage();
		}
	}

	private function downloadAndVerify($filename, $url, $hash)
	{

		if (!$hash || !$filename) return false;

		$dirname = dirname($filename);
		$this->fileHelper->mkdirIfNotExists($dirname);

		if (!$this->fileHelper->file_exists($filename)) {
			if($this->islog == 1){
				$this->log->fileLog('下载文件', $url, $filename);
			}
			$this->net->downloadFile($url, $filename);
		}

		$flag = $this->hashVerify($filename, $hash);

		if ($flag !== true) {
			$this->net->downloadFile($url, $filename);
			$flag = $this->hashVerify($filename, $hash);
		}

		return $flag;
	}

	private function verifyFilesHash()
	{
		$fin = $this->getFingerprintHash();
		if ($fin === false) {
			return '指纹文件异常';
		}

		$times = 3;
		for ($i = 0; $i < $times; $i++) {
			$downmsg = $this->downloadFingerprint($fin);
			if ($downmsg === true) {
				$verify = $this->hashVerify($this->fileHashPath, $fin->getFileHash());
				if ($verify === true) return true;
			}
		}

		return '文件指纹校验失败';
	}

	private function verifyDbHash()
	{
		$fin = $this->getFingerprintHash();
		if ($fin === false) {
			return '指纹文件异常';
		}

		$times = 3;
		for ($i = 0; $i < $times; $i++) {
			$downmsg = $this->downloadFingerprint($fin);
			if ($downmsg === true) {
				$verify = $this->hashVerify($this->dbHashPath, $fin->getDBVerifyHash());
				if ($verify === true) return true;
			}
		}

		return '文件指纹校验失败';
	}

	public function getDiffMap()
	{
		$verifyResult = $this->verifyFilesHash();
		if ($verifyResult !== true) {
			throw new \Exception($verifyResult);
		}

		$fi       = new FileInfo();
		$fm       = new FileManager($this->fileHashPath, $fi);
		$hashList = $fm->loadList();

		$diff = $this->update_diff_hash_version($hashList, $this->dest);

		return $diff;
	}

	private function fileInfo2FileDiff($fileInfo)
	{
		$localname = $this->dest . $fileInfo->filename;

		$local = $this->getLocalFileInfoByFilename($fileInfo->filename);

		$fileDiff                 = new FileDiff();
		$fileDiff->filename       = $fileInfo->filename;
		$fileDiff->filename_local = $localname;
		$fileDiff->hash           = $fileInfo->hash;
		$fileDiff->hash_local     = $this->fileHash($localname);
		$fileDiff->update_time    = $fileInfo->update_time;
		//$fileDiff->update_time_local = filemtime( $this->dest .  $local->filename);// $local->update_time;
		$fileDiff->download_url  = $fileInfo->url;//  $fileInfo->getDownname();
		$fileDiff->download_name = $fileInfo->getDownname();//$local->update_time;

		if ($this->fileHelper->file_exists($this->dest . $fileInfo->filename)) {
			$fileDiff->update_time_local = date('Y-m-d H:i:s', filemtime($this->dest . $fileInfo->filename));
		} else {
			$fileDiff->update_time_local = '';
		}


		if ($this->fileHelper->file_exists($localname)) {
			$fileDiff->update_type = 'update';
		} else {
			$fileDiff->update_type = 'new';
		}

		return $fileDiff;
	}


	public function getLocalFileInfoByFilename($filename)
	{
		$fi       = new FileInfo();
		$fm       = new FileManager($this->localFileInfoPath, $fi);
		$fileinfo = $fm->findObject($filename);

		return $fileinfo;
	}

	public function downloadFileByFilename($filename)
	{
		$files = $this->getUpgradeFiles();

		foreach ($files as $f) {
			if ($f->filename == $filename) {
				$downname = $f->getDownname();
				$hash     = $f->hash;
				$file     = $f;
			}
		}

		if (!$file) {
			return '文件不存在';
		}


		$path = $this->downloadDir . '/' . $downname;
		$this->fileHelper->mkdirIfNotExists(dirname($path));

		try {
			$downloadUrl = $file->url . $this->authParams();
			if($this->islog == 1){
				$this->log->fileLog('下载文件', $downloadUrl, $path);
			}
			$this->net->downloadFile($downloadUrl, $path);

			$verifyed = $this->hashVerify($path, $hash);

			if (!$verifyed) {
				return "文件校验失败,请重新下载";
			} else {
				return true;
			}
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function downloadFilesByFilenames($keys)
	{
		if (empty($keys)) {
			return array('result' => false, 'text' => '参数异常');
		}

		$files = explode(',', $keys);

		$ret = array();
		foreach ($files as $file) {
			$data   = array('key' => $file);
			$result = $this->downloadFileByFilename($file);
			if ($result !== true) {
				$data['result'] = false;
				$data['text']   = $result;
			} else {
				$data['result'] = true;
				$data['text']   = '';
			}

			$ret[] = $data;
		}

		$ret['result'] = true;
		$ret['text']   = '';

		return $ret;
	}

	public function getText($filename)
	{
		$files = $this->getUpgradeFiles();

		foreach ($files as $file) {
			if ($file->filename == $filename) {
				$text = $this->net->downloadText($file->url . $this->authParams());

				return $text;
			}
		}

		return "文件不存在";
	}

	public function verifyText($datas=array())
	{
		$params = '';
		if(!empty($datas)){
			foreach ($datas as $k => $v) {
				$params .= "&$k=$v";
			}
		}

		$text = $this->net->downloadText($this->registerUrl . $params);
		$result  = json_decode($text, true);
		if ($result['result'] == true) {
			if($result['data']){
				$ret = array(
					'result' => true,
					'text'   => $result['text'],
					'data'   => $result['data']
				);
				}else{
				$ret = array(
					'result' => true,
					'text'   => $result['text']
				);
			}

		} else {
			$ret = array(
				'result' => false,
				'text'   => $result['text']
			);
		}
		return $ret;
	}

	public function checkText($datas=array())
	{
		$params = '';
		if(!empty($datas)){
			foreach ($datas as $k => $v) {
				$params .= "&$k=$v";
			}
		}

		$text = $this->net->downloadText($this->checkUrl . $params);

		$result  = json_decode($text, true);

		if ($result['result'] == true) {
			if(isset($result['data'])){
				$ret = array(
					'result' => true,
					'text'   => $result['text'],
					'data'   => $result['data']
				);
				}else{
                    $ret = array(
                        'result' => true,
                        'text'   => $result['text']
                    );
			}

		} else {
			$ret = array(
				'result' => false,
				'text'   => $result['text']
			);
		}
		return $ret;
	}

	private function getUpgradeFiles()
	{
		if (!$this->fileHelper->file_exists($this->fileHashPath)) {
			$result = $this->downloadFingerprint();
			if (true !== $result) {
				throw new \Exception($result);
			}
		}

		$fm    = new FileManager($this->fileHashPath, new FileInfo());
		$files = $fm->loadList();

		return $files;
	}

	private function upgradeResult($result, $text, $err_files)
	{
		return array(
			'result'    => $result,
			'text'      => $text,
			'err_files' => $err_files,
		);
	}

	private function upgradeDo($diffmap, $sqls)
	{
		set_time_limit(0);

		if (!$this->verifyDownloadFiles($diffmap)) {
			return $this->upgradeResult(false, '文件校验失败,请重新下载文件', null);
		}
		$notWriteableDirs = $this->checkWriteableByDiff($diffmap);
		if (count($notWriteableDirs) > 0) {
			$err_msg = '';
			foreach ($notWriteableDirs as $dir) {
				if($this->islog == 1){
					$this->log->log('目录不可写入:' . $dir, Log::ERROR);
				}
				$err_msg .= $dir . "<br/>";
			}

			return $this->upgradeResult(false, '目录不可写入:<br/>' . $err_msg, null);
		}

		$versionInfo = $this->getLocalVersionInfo();
		$version_local = $versionInfo->version;
		//$backupDir = $this->backupFileByDiff($diffmap);
		$backupDir = $this->backupFileByDiff($diffmap,$version_local);

		$pdo = $this->getPdo();
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$pdo->beginTransaction();
		$db_status = $this->updateDB($pdo, $sqls);


		if ($db_status !== true) {
			$pdo->rollBack();

			return $this->upgradeResult(false, '数据库更新异常', null);
		}

		$err_files = $this->replaceFiles($diffmap);

		if (empty($err_files)) {
			$pdo->commit();

			//$this->writeVersionInfo();

			//$this->clearTempFiles();

			if ($this->fileHelper->file_exists($backupDir)) {
				$text = "更新完成！您网站的原文件备份存放在$backupDir,<br/>如果系统有异常，请将此目录文件恢复";
			} else {
				$text = "更新成功！";
			}

			//$this->after();

			return $this->upgradeResult(true, $text, null);
		} else {
			$pdo->rollBack();
			foreach ($err_files as $efile) {
				if($this->islog == 1){
					$this->log->log('更新文件出错:' . $efile, Log::ERROR);
				}
			}

			return $this->upgradeResult(false, '程序更新失败!', $err_files);
		}
	}


	private function report($new)
	{
		$url = $this->reportUrl . "&new_version={$new}";

		try {
			$this->net->downloadText($url);
		} catch (\Exception $e) {
		}
	}

	public function initVersion()
	{

		if (!$this->fileHelper->file_exists($this->localInfoPath)) {
			$this->fileHelper->mkdirIfNotExists(dirname($this->localInfoPath));
			if ($this->fileHelper->file_exists(dirname($this->localInfoPath))) {
				$fm = new FileManager($this->localInfoPath, new LocalVersionInfo());
				$fm->load();
				$fm->write();
			}
		}

	}

	public function init()
	{
		try {
			return $this->runExec('before');
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function after()
	{
		try {
			return $this->runExec('after');
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	private function runExec($action)
	{
		$versionInfo = $this->getLocalVersionInfo();
		$version_local = $this->versionToInteger($versionInfo->version);
		$files = $this->getScriptFiles();
		$down_files = array();
		if ($action == 'before') {
			foreach ($files as $key=>$file) {
				if ($file->upsort == 'before' && $version_local < $this->versionToInteger($key)) {
					$down_files[$this->versionToInteger($key)]['file'] = $file->filename;
					$down_files[$this->versionToInteger($key)]['url'] = $file->url;
					$down_files[$this->versionToInteger($key)]['hash'] = $file->hash;
				}
			}
		} elseif ($action == 'after') {
			foreach ($files as $key=>$file) {
				if ($file->upsort == 'after' && $version_local <= $this->versionToInteger($key)) {
					$down_files[$this->versionToInteger($key)]['file'] = $file->filename;
					$down_files[$this->versionToInteger($key)]['url'] = $file->url;
					$down_files[$this->versionToInteger($key)]['hash'] = $file->hash;
				}
			}
		}
		$lists = array();
		foreach ($down_files as $keys=>$files) {
			if (!$this->fileHelper->file_exists($this->filescriptPath .'/'. $files['file'])) {
				$this->fileHelper->mkdirIfNotExists(dirname($this->filescriptPath .'/'. $files['file']));
				$this->net->downloadFile($files['url'] . $this->authParams(),$this->filescriptPath .'/'. $files['file']);
			}
			$verifyed = $this->hashVerify($this->filescriptPath .'/'. $files['file'], $files['hash']);

			if (!$verifyed) {
				return "文件校验失败,请重新下载";
			} else {
				$lists[] = $this->filescriptPath .'/'. $files['file'];
			}
		}
		return $lists;
	}

	public function upgrade()
	{
		try {
			$diff = $this->getDiffMap();
			$sqls = $this->getDatabaseSource();

			if (count($diff) < 1 && count($sqls) < 1) {
				throw new \Exception('您的版本已经是最新,无需升级');
			}

			return $this->upgradeDo($diff, $sqls);
		} catch (\Exception $e) {
			return $this->upgradeResult(false, $e->getMessage(), null);
		}
	}

	public function updateDB($pdo, $sqls)
	{

		$errorSqls = array();
		foreach ($sqls as $sql) {
			if(!empty($sql)){
				try {
					if($this->islog == 1){
						$this->log->log('执行sql:' . $sql);
					}
					$pdo->exec($sql);
				} catch (\Exception $e) {
					$errorSqls[] = array(
						'sql'  => $sql,
						'text' => $e->getMessage()
					);
					if($this->islog == 1){
						$this->log->log('数据库更新异常:' . $sql . " ### " . $e->getMessage(), Log::ERROR);
					}
				}
			}
		}

		if (empty($errorSqls)) {
			return true;
		} else {
			return $errorSqls;
		}
	}

	private function getPdo()
	{
		$host   = $this->dbConfig['db_host'];
		$user   = $this->dbConfig['db_user'];
		$pass   = $this->dbConfig['db_password'];
		$dbname = $this->dbConfig['db_name'];
		$port   = $this->dbConfig['db_port'];

		$connStr = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
		$conn    = new \PDO($connStr, $user, $pass);

		return $conn;
	}

	public function getDatabaseSource()
	{
		set_time_limit(0);

		$t = new SchemaMetaInfoHelper($this->dbConfig['db_host'],
									  $this->dbConfig['db_user'],
									  $this->dbConfig['db_password'],
									  $this->dbConfig['db_port']);
		$old = $t->getTables($this->dbConfig['db_name']);
		$new = $this->getMasterTableMap();
		// cre$sqlstable statements
		$sqls = $this->getCreateTableSqls($new->toArray(), $old);
		// add column statements
		$addColumnSqls = $this->getAddColumnSqls($new->toArray(), $old);
		// change column statements
		$changeColumnSqls = $this->getChangeColumnSqls($new->toArray(), $old);

		foreach ($addColumnSqls as $sql) {
			$sqls[] = $sql;
		}
		foreach ($changeColumnSqls as $sql) {
			$sqls[] = $sql;
		}

		$versionInfo = $this->getLocalVersionInfo();
		$execSqls    = $this->getDbExecSqlByVersion($versionInfo->version);

		foreach ($execSqls as $sql) {
			$sqls[] = $sql;
		}

		return $sqls;
	}

	private function getDbExecSqlByVersion($version)
	{
		$fm          = new FileManager($this->dbExecHashPath, new DBExec());
		$dbexec      = $fm->load();
		$sqlVersions = $dbexec->toArray();

		return $this->getDbExecSqlByVersion2($version, $sqlVersions);
	}

	public function getDbExecSqlByVersion2($version, $sqlVersions) {
		$version_i = $this->versionToInteger($version);

		$sqlList = array();
		foreach ($sqlVersions as $k=>$v) {
			$sqlList[ $this->versionToInteger($k)] = $v;
		}
		ksort($sqlList);

		$list = array();
		foreach ($sqlList as $sk=>$sv) {
			if ($version_i < $this->versionToInteger($sk)) {
				foreach ($sv as $kk=>$vv) {
					$list[] = $vv;
				}
			}
		}

		return $list;
	}

	public function versionToInteger($version) {
		$vids = explode('.', $version);
		$i = 0;
		foreach ($vids as $id) {
			$i *= 1000;
			$i += $id;
		}

		return $i;
	}

	private function verifyDownloadFiles($diffMap)
	{
		foreach ($diffMap as $diff) {
			$file = $this->getDownpath($diff);
			if (!$this->hashVerify($file, $diff->hash)) {
				if($this->islog == 1){
					$this->log->log('文件校验不通过:' . $file, Log::ERROR);
				}
				return false;
			}
		}

		return true;
	}

	public function getMasterTableMap()
	{
		$fm = new FileManager($this->dbHashPath, new TableMeta());
		$tm = $fm->load();

		return $tm;
	}


	public function getCreateTableSqls($master, $slave)
	{
		$schemaDiff = new SchemaDiff();
		$newtable   = $schemaDiff->findCreateTables($master, $slave);

		$sqls = array();
		foreach ($newtable as $t) {
			$sqls[] = $this->sqlStatementGenerate->generateCreateTableSql($t[SchemaMetaInfoHelper::TABLE_NAME],
																		  $t[SchemaMetaInfoHelper::TABLE_COLUMNS],
																		  $t[SchemaMetaInfoHelper::TABLE_PRIMARY_KEYS],
																		  $t[SchemaMetaInfoHelper::TABLE_UNIQUE_INDEXS]);
		}

		return $sqls;
	}

	public function getAddColumnSqls($master, $slave)
	{
		$schemaDiff = new SchemaDiff();
		$tables     = $schemaDiff->findExistsTables($master, $slave);

		$sqls = array();
		foreach ($tables as $k => $table) {
			$diffColumns = $schemaDiff->findNotExistsColumns($table[SchemaMetaInfoHelper::TABLE_COLUMNS],
															 $slave[$k][SchemaMetaInfoHelper::TABLE_COLUMNS]);
			if (!empty($diffColumns)) {
				$sqls[] = $this->sqlStatementGenerate->generateAlterAddColumnSql($table[SchemaMetaInfoHelper::TABLE_NAME],
																				 $diffColumns);
			}
		}

		return $sqls;
	}

	public function getChangeColumnSqls($master, $slave)
	{

		$schemaDiff = new SchemaDiff();
		//$ctables = $schemaDiff->findExistsTables($newMap, $oldMap);
		$tables = $schemaDiff->findChangeColumnTablesDiff($master, $slave);
		$sqls   = array();
		foreach ($tables as $t) {
			$sqls[] = $this->sqlStatementGenerate->generateAlterChangeColumnSql($t[SchemaMetaInfoHelper::TABLE_NAME],
																				$t[SchemaMetaInfoHelper::TABLE_COLUMNS]);
		}

		return $sqls;
	}

	//db code end

 	private function conParams()
	{
		$versionInfo = $this->getLocalVersionInfo();

		$arr = array(
			'host'                  => $_SERVER['HTTP_HOST'],
			'appid'                 => $this->apphash['appid'],
			'appuid'                => $this->apphash['appuid'],
			'appname'               => $this->apphash['appname'],
			'domain'                => $this->apphash['domain'],
            'is_https'               => $this->apphash['is_https'],
			'ip'                	=> $this->apphash['ip'],
			//'time'                  => $this->regTime,
            'time'                  => $this->apphash['time'],
			'token'                 => $this->apphash['token'],
			'email'                 => $this->apphash['email'],
			'version'               => $versionInfo->version,
			'update_engine_version' => $versionInfo->updateEngineVersion,
		);

		$params = '';
		foreach ($arr as $k => $v) {
			$params .= "&$k=$v";
		}

		return $params;
	}

	private function authParams()
	{
		$versionInfo = $this->getLocalVersionInfo();
		$access_arr = $this->get_access_token();
		$access_token= is_array($access_arr) ? $access_arr['data'] : '';
		$access_token=str_replace("+","%2B",$access_token);
		$arr = array(
			'access_token'          => $access_token,
			'appid'                 => $this->apphash['appid'],
			'appuid'                => $this->apphash['appuid'],
			'appname'               => $this->apphash['appname'],
			//'domain'                => $this->domain,
			//'is_https'               => $this->isHttps,
			//'ip'                    => $this->ip,
			//'time'                  => $this->regTime,
            'time'                  => $this->apphash['time'],
			//'token'                 => $this->token,
			'email'                 => $this->apphash['email'],
			'version'               => $versionInfo->version,
			'update_engine_version' => $versionInfo->updateEngineVersion,
		);

		$params = '';
		foreach ($arr as $k => $v) {
			$params .= "&$k=$v";
		}

		return $params;
	}

	public function getLogList()
	{
		$list = array(
			'error.log',
			'info.log',
			'warning.log'
		);

		return $list;
	}

	public function getLogMessage($filename)
	{
		$file = $this->tempDir . '/log/' . $filename;
		if ($this->fileHelper->file_exists($file)) {
			return file_get_contents($file);
		}

		return '';
	}

	public function logDownload($filename)
	{
		$file = $this->tempDir . '/log/' . $filename;

		if ($this->fileHelper->file_exists($file)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($file) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			readfile($file);
			exit;
		}
	}

	public function getnoupdate($type = 'str')
	{
		$file = $this->noupdateInfoPath;

		$info = new Noupdate();

		$fm = new FileManager($file, $info);

		$info = $fm->load();

		$pathsarr = @json_decode($info->paths,true);

		if(is_array($pathsarr) && !empty($pathsarr)){
			$paths = array();

			if($type == 'arr'){
				foreach($pathsarr as $key=>$val){
					$paths[] = $val['name'];
				}
				return $paths;
			}else{
				foreach($pathsarr as $key=>$val){
					$paths[] = $val['name'];
				}
				return implode("\r\n", $paths);
			}

		}else{
			if($type == 'arr'){
				return array();
			}else{
				return '';
			}

		}
		//return $info;
	}

	public function putnoupdate($noupdatefiles)
	{
		if(!empty($noupdatefiles)){
			$noupdatefiles = nl2br($noupdatefiles);
			$noupdatearr_new = array();
			$noupdatearr = explode("<br />", $noupdatefiles);
			if(is_array($noupdatearr)){
				$keys = 0;
				foreach($noupdatearr as $key=>$path){
					$path = trim($path);
					if(is_file($this->dest.$path)){
						$noupdatearr_new[$keys]['name'] = $path;
						$noupdatearr_new[$keys]['type'] = 'file';
					}else if(is_dir($this->dest.$path)){
						$noupdatearr_new[$keys]['name'] = $path;
						$noupdatearr_new[$keys]['type'] = 'dir';
					}
					$keys++;
				}
			}
		}else{
			$noupdatearr_new = array();
		}

		$paths = json_encode($noupdatearr_new);

		$file = $this->noupdateInfoPath;

		$noupdate = new Noupdate();

		$noupdate->paths           = $paths;
		$noupdate->updateTime      = date('Y-m-d H:i:s');
		$this->fileHelper->mkdirIfNotExists(dirname($this->noupdateInfoPath));
		$fm = new FileManager($file, $noupdate);
		$fm->write();
		return true;
	}

	public function getTextLocal($filename)
	{
		if(file_exists($this->dest.$filename)){
			$text = file_get_contents($this->dest.$filename);
			return $text;
		}

		return false;
	}

	public function noupdateCheck($path)
	{

		$noupdate = $this->getnoupdate('arr');
		foreach($noupdate as $key=>$filename){
			if(strpos($path,$filename) === 0){
				return true;
			}
		}

		return false;
	}

	public function getDestpath_($hash)
	{

		$downname = $hash->filename;

		if($this->noupdateCheck($downname)){
			$downname = $this->bakPathName($downname);
		}

		$path = $this->dest . $downname;

		return $path;
	}

	public function getFileName($filename,$suffix="_bak.")
	{

		$arr = explode('/', $filename);

		$filename = end($arr);

		return $filename;

	}

	public function bakFileName($filename, $suffix="_bak")
	{
		$arr = explode('/', $filename);
		$filename = end($arr);
		$pos = strrpos($filename,".");
		$prefix = substr($filename,0,$pos);
		$postfix = substr($filename,$pos,strlen($filename));
		$newstr = $prefix.$suffix.$postfix;
		return $newstr;

	}

	public function bakPathName($filename, $suffix="_bak")
	{
		$pos = strrpos($filename,".");
		$prefix = substr($filename,0,$pos);
		$postfix = substr($filename,$pos,strlen($filename));
		$newstr = $prefix.$suffix.$postfix;
		return $newstr;

	}

	public function zipArchive()
	{

		$old = "old";
		$new = "new";
		$versionInfo = $this->getLasterVersion();
		$version = $versionInfo->version;

		$zip_name = $version . ".zip";
		$open_path = $this->zipDir .'/'. $zip_name;
		$this->fileHelper->mkdirIfNotExists(dirname($open_path));
		$zip = new \ZipArchive();
		$zip->open($open_path, \ZipArchive::CREATE);
		$zip->addEmptyDir($old);
		$noupdate = $this->getnoupdate("arr");
		/*
		$ret = array(
			'result' => false,
			'text'   => $open_path
		);
		return $ret;
		*/

		foreach($noupdate as $key=>$value){

			$file_path = $this->dest . $value;
			if(strpos($value,"/") === 0){
				$file_paths = $old . $value;
			}else{
				$file_paths = $old .'/'. $value;
			}
			if(is_dir($file_path)){
				$this->addDirToZip($file_path,$zip,$this->dest,$old);
			}elseif(is_file($file_path) && file_exists($file_path)){
				$zip->addFile($file_path,$file_paths);
			}

		}

 		$zip = new \ZipArchive();
		$zip->open($open_path, \ZipArchive::CREATE);
		$zip->addEmptyDir($new);

		$diff = $this->getDiffMap();

		foreach($diff as $key=>$hash){
			$path = $hash->filename;
			if(in_array($path,$noupdate)){
				$down_path = $this->downloadDir . "/" . $hash->download_name;
				if(strpos($path,"/") === 0){
					$down_paths = $new . $path;
				}else{
					$down_paths = $new .'/'. $path;
				}
				if(file_exists($down_path)){
					$zip->addFile($down_path,$down_paths);
				}
			}
		}
		$zip->close();

		$ret = array(
			'result' => true,
			'path'   => $open_path,
			'name'   => $zip_name
		);
		return $ret;

	}

	public function addDirToZip($path,$zip,$rpath,$dir=''){
		foreach(@scandir($path) as $afile)
		{
			if($afile=='.'||$afile=='..') continue;
			if(is_dir($path.'/'.$afile))
			{
				$this->addDirToZip($path.'/'.$afile,$zip,$rpath,$dir);
			} else {
				if(!empty($dir)){
					$rpaths = str_replace($rpath,"",$path);
					$rpaths = $dir . $rpaths .'/'. $afile;
					$zip->addFile($path.'/'.$afile,$rpaths);
				}else{
					$zip->addFile($path.'/'.$afile,str_replace($rpath,"",$path).'/'.$afile);
				}

			}
		}
	}
	public function getBackDirs($searchType='',$keyword='',$firstrow=0,$pagesize=10){

		$lists = array();
		$list = scandir($this->backupDir);
		foreach($list as $key=>$value){
			if($value == '.' || $value == '..' || is_file($value)){
				continue;
			}
			$value_arr = explode('_',$value);
			if($searchType == 'version' && !empty($keyword)){
				if(strpos($value_arr[0], $keyword) !== false){
					$lists[$key]['version'] = $value_arr[0];
					$lists[$key]['date'] = $value_arr[1];
					$lists[$key]['name'] = $value;
				}
			}elseif($searchType == 'date' && !empty($keyword)){
				if(strpos($value_arr[1], $keyword) !== false){
					$lists[$key]['version'] = $value_arr[0];
					$lists[$key]['date'] = $value_arr[1];
					$lists[$key]['name'] = $value;
				}
			}else{
				$lists[$key]['version'] = $value_arr[0];
				$lists[$key]['date'] = $value_arr[1];
				$lists[$key]['name'] = $value;
			}
		}
		if(!empty($pagesize)){
			$lists = array_slice($lists,$firstrow,$pagesize);
		}
		return $lists;
	}
	public function rollBackDirs($dir_name){
		$oldDir = $this->backupDir .'/'. $dir_name;
		if(!$this->fileHelper->file_exists($oldDir)){
            $ret = array(
                'result' => false,
                'text'   => '备份目录不存在',
            );
            return $ret;
        }
		$aimDir = $this->dest;
		$result = $this->fileHelper->copyDir($oldDir, $aimDir, $overWrite = true);
		$ret = array(
			'result' => true,
			'text'   => '回滚成功',
		);
		return $ret;
	}

	private function getScriptFiles()
	{
		if (!$this->fileHelper->file_exists($this->execHashPath)) {
			$result = $this->downloadFingerprint();
			if (true !== $result) {
				throw new \Exception($result);
			}
		}

		$fm    = new FileManager($this->execHashPath, new FileScript());
		$files = $fm->loadLists();

		return $files;
	}

	public function getCloudModule($searchType='',$keyword='',$firstrow=0,$pagesize=30){

		$lists = array();

		$list = array();

		$url = $this->checkModuleUrl;

		/* $ret = array(
			'result' => false,
			'text'   => $url . $this->conParams()
		);
		return $ret;  */

		if (!$this->fileHelper->file_exists($this->modulesPath) || ($this->fileHelper->file_exists($this->modulesPath) && $this->cache_time + filectime($this->modulesPath) <= time())) {
			$result = $this->net->downloadText($url . $this->conParams());
			$arr = json_decode($result, true);

			if ($arr['result'] === true) {
				$list = $arr['data'];
				file_put_contents($this->modulesPath,json_encode($list));
			}
		}else{
			$list = json_decode(file_get_contents($this->modulesPath),true);
		}

		foreach($list as $module_appid=>$value){

			if($searchType == 'title' && !empty($keyword)){

				if(strpos($value['title'], $keyword) !== false){
					$lists[$module_appid]['title'] = $value['title'];
					$lists[$module_appid]['name'] = $value['name'];
					$lists[$module_appid]['version_cloud'] = $value['version_cloud'];
					$lists[$module_appid]['cate_name'] = $value['cate_name'];
					$lists[$module_appid]['cate_title'] = $value['cate_title'];
					$lists[$module_appid]['cate_path'] = $value['cate_path'];
					$lists[$module_appid]['use_way'] = $value['use_way'];
					$lists[$module_appid]['tryout_time'] = $value['tryout_time'];
					$lists[$module_appid]['price'] = $value['price'];
					$lists[$module_appid]['init_update_time'] = $value['init_update_time'];
				}
			}elseif($searchType == 'name' && !empty($keyword)){

				if(strpos($value['name'], $keyword) !== false){
					$lists[$module_appid]['title'] = $value['title'];
					$lists[$module_appid]['name'] = $value['name'];
					$lists[$module_appid]['version_cloud'] = $value['version_cloud'];
					$lists[$module_appid]['cate_name'] = $value['cate_name'];
					$lists[$module_appid]['cate_title'] = $value['cate_title'];
					$lists[$module_appid]['cate_path'] = $value['cate_path'];
					$lists[$module_appid]['use_way'] = $value['use_way'];
					$lists[$module_appid]['tryout_time'] = $value['tryout_time'];
					$lists[$module_appid]['price'] = $value['price'];
					$lists[$module_appid]['init_update_time'] = $value['init_update_time'];
				}
			}else{
				$lists = $list;
			}
		}
		if(!empty($pagesize)){
			$lists = array_slice($lists,$firstrow,$pagesize);
		}

		return $lists;

	}
	public function delModuleCache(){
		return $this->fileHelper->deleteFile($this->modulesPath);
	}
	public function arr_foreach2($arr,$paths,$signs = "") {
		mkdir($paths,0777,true);
		$files=$paths.$signs;
		if (!is_array($arr)) {
			$handles=fopen($files,"a+");
			fwrite($handles,$arr."\r\n");
			return false;
		}
		foreach ($arr as $key => $val) {
			if (is_array($val)) {
				$this->arr_foreach2($val,$paths,$signs);
			} else {
				$handles=fopen($files,"a+");
				fwrite($handles,$key.'=>'.$val."\r\n");
			}
		}
	}

}
