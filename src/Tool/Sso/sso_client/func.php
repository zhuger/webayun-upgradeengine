<?php
/** http 函数
 * fn_http function.
 *
 * @access public
 * @param mixed $str_url
 * @param mixed $arr_data
 * @param string $str_method (default: 'get')
 * @return void
 */
function fn_http($str_url, $arr_data, $str_method = 'get') {

    $_obj_http = curl_init();
    $_str_data = http_build_query($arr_data);

    $_arr_headers = array(
        'Content-Type: application/x-www-form-urlencoded',
        //'Content-length: ' . strlen($_str_data),
    );

    curl_setopt($_obj_http, CURLOPT_HTTPHEADER, $_arr_headers);

    if ($str_method == 'post') {
        curl_setopt($_obj_http, CURLOPT_POST, true);
        curl_setopt($_obj_http, CURLOPT_POSTFIELDS, $_str_data);
        curl_setopt($_obj_http, CURLOPT_URL, $str_url);
    } else {
        if (stristr($str_url, '?')) {
            $_str_conn = '&';
        } else {
            $_str_conn = '?';
        }
        curl_setopt($_obj_http, CURLOPT_URL, $str_url . $_str_conn . $_str_data);
    }

    curl_setopt($_obj_http, CURLOPT_RETURNTRANSFER, true);

    $_obj_ret = curl_exec($_obj_http);

    $_arr_return = array(
        'ret'   => $_obj_ret,
        'error' => curl_error($_obj_http),
        'errno' => curl_errno($_obj_http),
    );

    //print_r(curl_error($_obj_http));
    //print_r(curl_errno($_obj_http));
    //print_r($_obj_ret);

    curl_close($_obj_http);

    return $_arr_return;
}


/** JSON 编码（内容可编码成 base64）
 * fn_jsonEncode function.
 *
 * @access public
 * @param string $arr_json (default: '')
 * @param string $method (default: '')
 * @return void
 */
function fn_jsonEncode($arr_json = '', $encode = false) {
    if ($encode) {
        $_str_encode = 'encode';
    }

    if (fn_isEmpty($arr_json)) {
        $str_json = '';
    } else {
        $arr_json = fn_eachArray($arr_json, $_str_encode);
        //print_r($method);
        $str_json = json_encode($arr_json); //json编码
    }

    return $str_json;
}


/** JSON 解码 (内容可解码自 base64)
 * jsonDecode function.
 *
 * @access public
 * @param string $str_json (default: '')
 * @param string $method (default: '')
 * @return void
 */
function fn_jsonDecode($str_json = '', $decode = false) {
    if ($decode) {
        $_str_decode = 'decode';
    }
    if (fn_isEmpty($str_json)) {
        $arr_json = array();
    } else {
        $arr_json = json_decode($str_json, true); //json解码
        $arr_json = fn_eachArray($arr_json, $_str_decode);
    }

    return $arr_json;
}


/** 遍历数组，并进行 base64 解码编码
 * fn_eachArray function.
 *
 * @access public
 * @param mixed $arr
 * @param string $method (default: 'encode')
 * @return void
 */
function fn_eachArray($arr, $method = 'encode') {
    $_is_magic = get_magic_quotes_gpc();
    if (fn_isEmpty($arr)) {
        $arr = array();
    } else {
        foreach ($arr as $_key=>$_value) {
            if (fn_isEmpty($_value)) {
                switch ($method) {
                    case 'encode':
                        if (!$_is_magic) {
                            $_str = addslashes($_value);
                        } else {
                            $_str = $_value;
                        }
                        $arr[$_key] = base64_encode($_str);
                    break;

                    case 'decode':
                        $_str = base64_decode($_value);
                        //if (!$_is_magic) {
                            $arr[$_key] = stripslashes($_str);
                        //} else {
                            //$arr[$_key] = $_str;
                        //}
                    break;

                    default:
                        if (!$_is_magic) {
                            $_str = addslashes($_value);
                        } else {
                            $_str = $_value;
                        }
                        $arr[$_key] = $_str;
                    break;
                }
            } else {
                $arr[$_key] = fn_eachArray($_value, $method);
            }
        }
    }
    return $arr;
}


/** 随机字符串
 * fn_rand function.
 *
 * @access public
 * @param int $num_rand (default: 32)
 * @return void
 */
function fn_rand($num_rand = 32) {
    $_str_char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $_str_rnd = '';
    while (strlen($_str_rnd) < $num_rand) {
        $_str_rnd .= substr($_str_char, (rand(0, strlen($_str_char))), 1);
    }
    return $_str_rnd;
}


/** 获取查询串提交值
 * fn_get function.
 *
 * @access public
 * @param mixed $key
 * @return void
 */
function fn_get($key) {
    if (isset($_GET[$key])) {
        return $_GET[$key];
    } else {
        return null;
    }
}


/** 获取表单提交值
 * fn_post function.
 *
 * @access public
 * @param mixed $key
 * @return void
 */
function fn_post($key) {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    } else {
        return null;
    }
}

function fn_isEmpty($data) {
    if (!isset($data)) {
    	return true;
    }
	if ($data === null) {
		return true;
	}
	if (is_array($data) || is_object($data)) {
    	if (empty($data)) {
    		return true;
    	}
	} else {
    	if (empty($data) || trim($data) === '') {
    		return true;
    	}
	}

	return false;
}

/** 获取 IP
 * fn_getIp function.
 *
 * @access public
 * @return void
 */
function fn_getIp() {
    if (isset($_SERVER)) {
        if (fn_isEmpty(fn_server('REMOTE_ADDR'))) {
            $_str_ip = '0.0.0.0';
        } else {
            $_str_ip = fn_server('REMOTE_ADDR');
        }
    } else {
        if (fn_isEmpty(getenv('REMOTE_ADDR'))) {
            $_str_ip = '0.0.0.0';
        } else {
            $_str_ip = getenv('REMOTE_ADDR');
        }
    }
    return $_str_ip;
}

/** 封装 $_SERVER
 * fn_server function.
 *
 * @access public
 * @param mixed $key
 * @return void
 */
function fn_server($key) {
    if (isset($_SERVER[$key])) {
        return fn_safe($_SERVER[$key]);
    } else {
        return null;
    }
}

function fn_safe($str_string) {
    //正则剔除
    $_arr_dangerRegs = array(
        /* -------- 跨站 --------*/

        //html 标签
        '/<(script|frame|iframe|bgsound|link|blink|object|applet|embed|style|layer|ilayer|base|meta)(\s+\S*)*>/i',

        //html 标签结束
        '/<\/(script|frame|iframe|blink|object|applet|embed|style|layer|ilayer)>/i',

        //html 事件
        '/on\w+\s*=\s*("|\')?\S*("|\')?/i',

        //html 属性包含脚本
        '/(java|vb)script:\s*\S*/i',

        //js 对象
        '/(document|location)\s*\.\s*\S*/i',

        //js 函数
        '/(eval|alert|prompt|msgbox)\s*\(.*\)/i',

        //css
        '/expression\s*:\s*\S*/i',

        /* -------- sql 注入 --------*/

        //显示 数据库 | 表 | 索引 | 字段
        '/show\s+(databases|tables|index|columns)/i',

        //创建 数据库 | 表 | 索引 | 视图 | 存储过程 | 存储过程
        '/create\s+(database|table|(unique\s+)?index|view|procedure|proc)/i',

        //更新 数据库 | 表
        '/alter\s+(database|table)/i',

        //丢弃 数据库 | 表 | 索引 | 视图 | 字段
        '/drop\s+(database|table|index|view|column)/i',

        //备份 数据库 | 日志
        '/backup\s+(database|log)/i',

        //初始化 表
        '/truncate\s+table/i',

        //替换 视图
        '/replace\s+view/i',

        //创建 | 更改 字段
        '/(add|change)\s+column/i',

        //选择 | 更新 | 删除 记录
        '/(select|update|delete)\s+\S*\s+from/i',

        //插入 记录 | 选择到文件
        '/insert\s+into/i',

        //sql 函数
        '/load_file\s*\(.*\)/i',

        //sql 其他
        '/(outfile|infile)\s+("|\')?\S*("|\')/i',
    );

    //特殊字符 直接剔除
    $_arr_dangerChars = array(
        '\t', '\r', '\n', PHP_EOL
    );

    $_str_return = trim($str_string);

    $_str_return = str_ireplace(',', '|', $_str_return); //特殊字符，内部保留

    foreach ($_arr_dangerRegs as $_key=>$_value) {
        $_str_return = preg_replace($_value, '', $_str_return);
    }

    foreach ($_arr_dangerChars as $_key=>$_value) {
        $_str_return = str_ireplace($_value, '', $_str_return);
    }

    $_str_return = fn_htmlcode($_str_return);

    $_str_return = str_ireplace('!', '&#33;', $_str_return);
    $_str_return = str_ireplace('$', '&#36;', $_str_return);
    $_str_return = str_ireplace('%', '&#37;', $_str_return);
    $_str_return = str_ireplace('\'', '&#39;', $_str_return);
    $_str_return = str_ireplace('(', '&#40;', $_str_return);
    $_str_return = str_ireplace(')', '&#41;', $_str_return);
    $_str_return = str_ireplace('+', '&#43;', $_str_return);
    $_str_return = str_ireplace('-', '&#45;', $_str_return);
    $_str_return = str_ireplace(':', '&#58;', $_str_return);
    $_str_return = str_ireplace('=', '&#61;', $_str_return);
    $_str_return = str_ireplace('?', '&#63;', $_str_return);
    //$_str_return = str_ireplace('@', '&#64;', $_str_return);
    $_str_return = str_ireplace('[', '&#91;', $_str_return);
    $_str_return = str_ireplace(']', '&#93;', $_str_return);
    $_str_return = str_ireplace('^', '&#94;', $_str_return);
    $_str_return = str_ireplace('`', '&#96;', $_str_return);
    $_str_return = str_ireplace('{', '&#123;', $_str_return);
    $_str_return = str_ireplace('}', '&#125;', $_str_return);
    $_str_return = str_ireplace('~', '&#126;', $_str_return);

    return $_str_return;
}

function fn_htmlcode($str_html, $method = 'encode', $spec = false) {
    switch ($method) {
        case 'decode':
            $str_html = html_entity_decode($str_html, ENT_QUOTES, 'UTF-8');

            switch ($spec) {
                case 'json': //转换 json 特殊字符
                    $str_html = str_ireplace('&#58;', ':', $str_html);
                    $str_html = str_ireplace('&#91;', '[', $str_html);
                    $str_html = str_ireplace('&#93;', ']', $str_html);
                    $str_html = str_ireplace('&#123;', '{', $str_html);
                    $str_html = str_ireplace('&#125;', '}', $str_html);
                    $str_html = str_ireplace('|', ',', $str_html);
                break;
                case 'url': //转换 加密 特殊字符
                    $str_html = str_ireplace('&#58;', ':', $str_html);
                    $str_html = str_ireplace('&#45;', '-', $str_html);
                    $str_html = str_ireplace('&#61;', '=', $str_html);
                    $str_html = str_ireplace('&#63;', '?', $str_html);
                break;
                case 'crypt': //转换 加密 特殊字符
                    $str_html = str_ireplace('&#37;', '%', $str_html);
                break;
                case 'base64': //转换 base64 特殊字符
                    $str_html = str_ireplace('&#61;', '=', $str_html);
                break;
            }
        break;
        default:
            $str_html = htmlentities($str_html, ENT_QUOTES, 'UTF-8');
        break;
    }

    return $str_html;
}