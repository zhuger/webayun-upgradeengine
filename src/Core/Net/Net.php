<?php
namespace webayun\upgradeengine\Core\Net;


class Net
{
	public function downloadFile($url, $filename) {
		set_time_limit(0);
		$fp = fopen($filename, 'wb');
		$options = array(
			CURLOPT_FILE    => $fp,
			CURLOPT_TIMEOUT =>  3600,
			CURLOPT_URL     => $url,
		);

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_exec($ch);
		fclose($fp);
		curl_close($ch);
	}

	/**
	 *
	 * @param $url
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function downloadText($url)
	{
		set_time_limit(60);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);


		$result = curl_exec($ch);

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($httpCode !== 200 || false === $result) {
			$err = curl_error($ch);
			curl_close($ch);
			throw new \Exception('网络异常:' . $err);
		}

		curl_close($ch);
		return $result;
	}

	/**
	 * 简单实现检测服务器检测
	 * @return bool true 正常
	 */
	public function test($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_exec($ch);

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode === 200) {
			return true;
		} else {
			$err = curl_error($ch);
			return $err;
		}
	}

	/**
	 * 高级CURL
	 */

	public function is_neterr($data) {
		if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && $data['errno'] == 0)) {
			return false;
		} else {
			return true;
		}
	}
	public function neterr($errno, $message = '') {
		return array(
			'errno' => $errno,
			'message' => $message,
		);
	}
	public function netstrexists($string, $find) {
		return !(strpos($string, $find) === FALSE);
	}
	public function random($length, $numeric = FALSE) {
		$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
		if ($numeric) {
			$hash = '';
		} else {
			$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
			$length--;
		}
		$max = strlen($seed) - 1;
		for ($i = 0; $i < $length; $i++) {
			$hash .= $seed[mt_rand(0, $max)];
		}
		return $hash;
	}
	public function ihttp_request($url, $post = '', $extra = array(), $timeout = 60) {
			if (function_exists('curl_init') && function_exists('curl_exec') && $timeout > 0) {
			$ch = $this->ihttp_build_curl($url, $post, $extra, $timeout);
			if ($this->is_neterr($ch)) {
				return $ch;
			}
			$data = curl_exec($ch);
			$status = curl_getinfo($ch);
			$errno = curl_errno($ch);
			$error = curl_error($ch);
			curl_close($ch);
			if ($errno || empty($data)) {
				return $this->neterr(1, $error);
			} else {
				return $this->ihttp_response_parse($data);
			}
		}
		$urlset = $this->ihttp_parse_url($url, true);
		if (!empty($urlset['ip'])) {
			$urlset['host'] = $urlset['ip'];
		}

		$body = $this->ihttp_build_httpbody($url, $post, $extra);

		if ($urlset['scheme'] == 'https') {
			$fp = $this->ihttp_socketopen('ssl://' . $urlset['host'], $urlset['port'], $errno, $error);
		} else {
			$fp = $this->ihttp_socketopen($urlset['host'], $urlset['port'], $errno, $error);
		}
		stream_set_blocking($fp, true);
		stream_set_timeout($fp, $timeout);
		if (!$fp) {
			return $this->neterr(1, $error);
		} else {
			fwrite($fp, $body);
			if($timeout > 0) {
				$content = '';
				while (!feof($fp)) {
					$content .= fgets($fp, 512);
				}
			}
			fclose($fp);
			return $this->ihttp_response_parse($content, true);
		}
	}

	public function ihttp_multi_request($urls, $posts = array(), $extra = array(), $timeout = 60) {
		if (!is_array($urls)) {
			return $this->neterr(1, '请使用ihttp_request函数');
		}
		$curl_multi = curl_multi_init();
		$curl_client = $response = array();

		foreach ($urls as $i => $url) {
			if (isset($posts[$i]) && is_array($posts[$i])) {
				$post = $posts[$i];
			} else {
				$post = $posts;
			}
			if (!empty($url)) {
				$curl = $this->ihttp_build_curl($url, $post, $extra, $timeout);
				if ($this->is_neterr($curl)) {
					continue;
				}
				if (curl_multi_add_handle($curl_multi, $curl) === CURLM_OK) {
									$curl_client[] = $curl;
				}
			}
		}
		if (!empty($curl_client)) {
			$active = null;
			do {
				$mrc = curl_multi_exec($curl_multi, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

			while ($active && $mrc == CURLM_OK) {
				if (curl_multi_select($curl_multi) != -1) {
					do {
						$mrc = curl_multi_exec($curl_multi, $active);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				} else {
					return $this->neterr(2, '请求失败，请检查URL');
				}
			}
		}

		foreach ($curl_client as $i => $curl) {
			$response[$i] = curl_multi_getcontent($curl);
			curl_multi_remove_handle($curl_multi, $curl);
		}
		curl_multi_close($curl_multi);
		return $response;
	}

	public function ihttp_socketopen($hostname, $port = 80, &$errno, &$errstr, $timeout = 15) {
		$fp = '';
		if(function_exists('fsockopen')) {
			$fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
		} elseif(function_exists('pfsockopen')) {
			$fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
		} elseif(function_exists('stream_socket_client')) {
			$fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
		}
		return $fp;
	}


	public function ihttp_response_parse($data, $chunked = false) {
		$rlt = array();
		$headermeta = explode('HTTP/', $data);
		if (count($headermeta) > 2) {
			$data = 'HTTP/' . array_pop($headermeta);
		}
		$pos = strpos($data, "\r\n\r\n");
		$split1[0] = substr($data, 0, $pos);
		$split1[1] = substr($data, $pos + 4, strlen($data));

		$split2 = explode("\r\n", $split1[0], 2);
		preg_match('/^(\S+) (\S+) (.*)$/', $split2[0], $matches);
		$rlt['code'] = $matches[2];
		$rlt['status'] = $matches[3];
		$rlt['responseline'] = $split2[0];
		$header = explode("\r\n", $split2[1]);
		$isgzip = false;
		$ischunk = false;
		foreach ($header as $v) {
			$pos = strpos($v, ':');
			$key = substr($v, 0, $pos);
			$value = trim(substr($v, $pos + 1));
			if (is_array($rlt['headers'][$key])) {
				$rlt['headers'][$key][] = $value;
			} elseif (!empty($rlt['headers'][$key])) {
				$temp = $rlt['headers'][$key];
				unset($rlt['headers'][$key]);
				$rlt['headers'][$key][] = $temp;
				$rlt['headers'][$key][] = $value;
			} else {
				$rlt['headers'][$key] = $value;
			}
			if(!$isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
				$isgzip = true;
			}
			if(!$ischunk && strtolower($key) == 'transfer-encoding' && strtolower($value) == 'chunked') {
				$ischunk = true;
			}
		}
		if($chunked && $ischunk) {
			$rlt['content'] = $this->ihttp_response_parse_unchunk($split1[1]);
		} else {
			$rlt['content'] = $split1[1];
		}
		if($isgzip && function_exists('gzdecode')) {
			$rlt['content'] = gzdecode($rlt['content']);
		}

		$rlt['meta'] = $data;
		if($rlt['code'] == '100') {
			return $this->ihttp_response_parse($rlt['content']);
		}
		return $rlt;
	}

	public function ihttp_response_parse_unchunk($str = null) {
		if(!is_string($str) or strlen($str) < 1) {
			return false;
		}
		$eol = "\r\n";
		$add = strlen($eol);
		$tmp = $str;
		$str = '';
		do {
			$tmp = ltrim($tmp);
			$pos = strpos($tmp, $eol);
			if($pos === false) {
				return false;
			}
			$len = hexdec(substr($tmp, 0, $pos));
			if(!is_numeric($len) or $len < 0) {
				return false;
			}
			$str .= substr($tmp, ($pos + $add), $len);
			$tmp  = substr($tmp, ($len + $pos + $add));
			$check = trim($tmp);
		} while(!empty($check));
		unset($tmp);
		return $str;
	}


	public function ihttp_parse_url($url, $set_default_port = false) {
		if (empty($url)) {
			return $this->neterr(1);
		}
		$urlset = parse_url($url);
		if (!empty($urlset['scheme']) && !in_array($urlset['scheme'], array('http', 'https'))) {
			return $this->neterr(1, '只能使用 http 及 https 协议');
		}
		if (empty($urlset['path'])) {
			$urlset['path'] = '/';
		}
		if (!empty($urlset['query'])) {
			$urlset['query'] = "?{$urlset['query']}";
		}
		if ( $this->netstrexists($url, 'https://') && !extension_loaded('openssl')) {
			if (!extension_loaded("openssl")) {
				return $this->neterr('请开启您PHP环境的openssl', '', '');
			}
		}

		if ($set_default_port && empty($urlset['port'])) {
			$urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
		}

		return $urlset;
	}


	public function ihttp_build_curl($url, $post, $extra, $timeout) {
		if (!function_exists('curl_init') || !function_exists('curl_exec')) {
			return $this->neterr(1, 'curl扩展未开启');
		}

		$urlset = $this->ihttp_parse_url($url);
		if ($this->is_neterr($urlset)) {
			return $urlset;
		}

		if (!empty($urlset['ip'])) {
			$extra['ip'] = $urlset['ip'];
		}

		$ch = curl_init();
		if (!empty($extra['ip'])) {
			$extra['Host'] = $urlset['host'];
			$urlset['host'] = $extra['ip'];
			unset($extra['ip']);
		}
		curl_setopt($ch, CURLOPT_URL, $urlset['scheme'] . '://' . $urlset['host'] . ($urlset['port'] == '80' || empty($urlset['port']) ? '' : ':' . $urlset['port']) . $urlset['path'] . $urlset['query']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		@curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		if ($post) {
			if (is_array($post)) {
				$filepost = false;
				foreach ($post as $name => &$value) {
					if (version_compare(phpversion(), '5.5') >= 0 && is_string($value) && substr($value, 0, 1) == '@') {
						$post[$name] = new CURLFile(ltrim($value, '@'));
					}
					if ((is_string($value) && substr($value, 0, 1) == '@') || (class_exists('CURLFile') && $value instanceof CURLFile)) {
						$filepost = true;
					}
				}
				if (!$filepost) {
					$post = http_build_query($post);
				}
			}
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		if (defined('CURL_SSLVERSION_TLSv1')) {
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
		if (!empty($extra) && is_array($extra)) {
			$headers = array();
			foreach ($extra as $opt => $value) {
				if ( $this->netstrexists($opt, 'CURLOPT_')) {
					curl_setopt($ch, constant($opt), $value);
				} elseif (is_numeric($opt)) {
					curl_setopt($ch, $opt, $value);
				} else {
					$headers[] = "{$opt}: {$value}";
				}
			}
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
			//curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		return $ch;
	}

	public function ihttp_build_httpbody($url, $post, $extra) {
		$urlset = $this->ihttp_parse_url($url, true);
		if ($this->is_neterr($urlset)) {
			return $urlset;
		}

		if (!empty($urlset['ip'])) {
			$extra['ip'] = $urlset['ip'];
		}

		$body = '';
		if (!empty($post) && is_array($post)) {
			$filepost = false;
			$boundary = $this->random(40);
			foreach ($post as $name => &$value) {
				if ((is_string($value) && substr($value, 0, 1) == '@') && file_exists(ltrim($value, '@'))) {
					$filepost = true;
					$file = ltrim($value, '@');

					$body .= "--$boundary\r\n";
					$body .= 'Content-Disposition: form-data; name="'.$name.'"; filename="'.basename($file).'"; Content-Type: application/octet-stream'."\r\n\r\n";
					$body .= file_get_contents($file)."\r\n";
				} else {
					$body .= "--$boundary\r\n";
					$body .= 'Content-Disposition: form-data; name="'.$name.'"'."\r\n\r\n";
					$body .= $value."\r\n";
				}
			}
			if (!$filepost) {
				$body = http_build_query($post, '', '&');
			} else {
				$body .= "--$boundary\r\n";
			}
		}

		$method = empty($post) ? 'GET' : 'POST';
		$fdata = "{$method} {$urlset['path']}{$urlset['query']} HTTP/1.1\r\n";
		$fdata .= "Accept: */*\r\n";
		$fdata .= "Accept-Language: zh-cn\r\n";
		if ($method == 'POST') {
			$fdata .= empty($filepost) ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data; boundary=$boundary\r\n";
		}
		$fdata .= "Host: {$urlset['host']}\r\n";
		$fdata .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1\r\n";
		if (function_exists('gzdecode')) {
			$fdata .= "Accept-Encoding: gzip, deflate\r\n";
		}
		$fdata .= "Connection: close\r\n";
		if (!empty($extra) && is_array($extra)) {
			foreach ($extra as $opt => $value) {
				if (! $this->netstrexists($opt, 'CURLOPT_')) {
					$fdata .= "{$opt}: {$value}\r\n";
				}
			}
		}
		if ($body) {
			$fdata .= 'Content-Length: ' . strlen($body) . "\r\n\r\n{$body}";
		} else {
			$fdata .= "\r\n";
		}
		return $fdata;
	}

	public function ihttp_get($url) {
		return $this->ihttp_request($url);
	}

	public function ihttp_post($url, $data, $timeout = 600) {
		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
			//'X-FORWARDED-FOR' =>'',
			//'CLIENT-IP'=>''
		);
		return $this->ihttp_request($url, $data, $headers, $timeout = 600);
	}
}
