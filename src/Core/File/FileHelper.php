<?php
namespace webayun\upgradeengine\Core\File;

class FileHelper
{
	private function handleFilename($file) {
		//为了兼容windows和linux上文件名编码不一致
		if (strpos(php_uname(), 'Windows')!== false) {
			$file = iconv("utf-8", "gbk//IGNORE", $file);
		}

		return $file;
	}

	public function mkdirIfNotExists($dirname) {
		if (!self::file_exists($dirname)) {
			$mk = mkdir($dirname, 0700, true);
			if (!$mk) {
				throw new \Exception('创建目录失败:' . $dirname);
			}
		}
	}

	public function deleteFile($file) {
		if (self::file_exists($file) && is_file($file)) {
			if (!unlink($file)) {
				return false;
			} else {
				return true;
			}
		}
	}

	public function deleteFolder($dir)
	{
		$handle = opendir($dir);
		if ($handle) {
			while (false !== ($item = readdir($handle))) {
				if ($item != "." && $item != "..") {
					(is_dir("$dir/$item")) ? self::deleteFolder("$dir/$item") : unlink("$dir/$item");
				}
			}
			closedir($handle);
			if (is_dir($dir)) {
				return rmdir($dir);
			}
		} else {
			if (file_exists($dir)) {
				return unlink($dir);
			} else {
				return false;
			}
		}
	}

	public function writeable($file) {
		$file = self::handleFilename($file);

		return is_writeable($file);
	}

	public function file_exists($file) {
		$file = self::handleFilename($file);

		return file_exists($file);
	}

/**
 * 操纵文件类
 *
 * 例子：
 * FileUtil::createDir('a/1/2/3');                测试建立文件夹 建一个a/1/2/3文件夹
 * FileUtil::createFile('b/1/2/3');               测试建立文件        在b/1/2/文件夹下面建一个3文件
 * FileUtil::createFile('b/1/2/3.exe');           测试建立文件        在b/1/2/文件夹下面建一个3.exe文件
 * FileUtil::copyDir('b','d/e');                  测试复制文件夹 建立一个d/e文件夹，把b文件夹下的内容复制进去
 * FileUtil::copyFile('b/1/2/3.exe','b/b/3.exe'); 测试复制文件        建立一个b/b文件夹，并把b/1/2文件夹中的3.exe文件复制进去
 * FileUtil::moveDir('a/','b/c');                 测试移动文件夹 建立一个b/c文件夹,并把a文件夹下的内容移动进去，并删除a文件夹
 * FileUtil::moveFile('b/1/2/3.exe','b/d/3.exe'); 测试移动文件        建立一个b/d文件夹，并把b/1/2中的3.exe移动进去
 * FileUtil::unlinkFile('b/d/3.exe');             测试删除文件        删除b/d/3.exe文件
 * FileUtil::unlinkDir('d');                      测试删除文件夹 删除d文件夹
 */

    /**
     * 建立文件夹
     *
     * @param string $aimUrl
     * @return viod
     */
    public function createDir($aimUrl) {
        $aimUrl = str_replace('', '/', $aimUrl);
        $aimDir = '';
        $arr = explode('/', $aimUrl);
        $result = true;
        foreach ($arr as $str) {
            $aimDir .= $str . '/';
            if (!file_exists($aimDir)) {
                $result = @mkdir($aimDir);
            }
        }
        return $result;
    }
    /**
     * 建立文件
     *
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public function createFile($aimUrl, $overWrite = false) {
        if (file_exists($aimUrl) && $overWrite == false) {
            return false;
        } elseif (file_exists($aimUrl) && $overWrite == true) {
            self :: unlinkFile($aimUrl);
        }
        $aimDir = dirname($aimUrl);
        self :: createDir($aimDir);
        touch($aimUrl);
        return true;
    }

    /**
     * 移动文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public function moveDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            self :: createDir($aimDir);
        }
        @ $dirHandle = opendir($oldDir);
        if (!$dirHandle) {
            return false;
        }
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                self :: moveFile($oldDir . $file, $aimDir . $file, $overWrite);
            } else {
                self :: moveDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        closedir($dirHandle);
        return rmdir($oldDir);
    }

    /**
     * 移动文件
     *
     * @param string $fileUrl
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public function moveFile($fileUrl, $aimUrl, $overWrite = false) {
        if (!file_exists($fileUrl)) {
            return false;
        }
        if (file_exists($aimUrl) && $overWrite = false) {
            return false;
        }elseif (file_exists($aimUrl) && $overWrite = true) {
            self :: unlinkFile($aimUrl);
        }
        $aimDir = dirname($aimUrl);
        self :: createDir($aimDir);
        rename($fileUrl, $aimUrl);
        return true;
    }

    /**
     * 删除文件夹
     *
     * @param string $aimDir
     * @return boolean
     */
    public function unlinkDir($aimDir) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        if (!is_dir($aimDir)) {
            return false;
        }
        $dirHandle = opendir($aimDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($aimDir . $file)) {
                self :: unlinkFile($aimDir . $file);
            } else {
                self :: unlinkDir($aimDir . $file);
            }
        }
        closedir($dirHandle);
        return rmdir($aimDir);
    }

    /**
     * 删除文件
     *
     * @param string $aimUrl
     * @return boolean
     */
    public function unlinkFile($aimUrl) {
        if (file_exists($aimUrl)) {
            unlink($aimUrl);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 复制文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public function copyDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            self :: createDir($aimDir);
        }
        $dirHandle = opendir($oldDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                self :: copyFile($oldDir . $file, $aimDir . $file, $overWrite);
            } else {
                self :: copyDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        return closedir($dirHandle);
    }

    /**
     * 复制文件
     *
     * @param string $fileUrl
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    public function copyFile($fileUrl, $aimUrl, $overWrite = false) {
        if (!file_exists($fileUrl)) {
            return false;
        }
        if (file_exists($aimUrl) && $overWrite == false) {
            return false;
        } elseif (file_exists($aimUrl) && $overWrite == true) {
            self :: unlinkFile($aimUrl);
        }
        $aimDir = dirname($aimUrl);
        self :: createDir($aimDir);
        copy($fileUrl, $aimUrl);
        return true;
    }


}
