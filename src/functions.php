<?php

if (!function_exists('weba_template')) {
    function weba_template($filename, $is_sys = 0, $flag = "TEMPLATE_DISPLAY")
    {
        global $_W;
        $sysTpye = 'WESHOP';
        if($sysTpye=="WE8"){
            $realPaths = IA_ROOT;
            $viewPths = "WE8";
        }elseif($sysTpye=="WESHOP"){
            $realPaths = app()->getRootPath();
            $viewPths = "WESHOP";
        }else{
            $realPaths = app()->getRootPath();
            $viewPths = "WESHOP";
        }

        //$source = $is_sys ? $realPaths . "/web/themes/{$_W['template']}/{$filename}.html" : './View/' . "{$filename}.html";
        $source = __DIR__ . "/View/{$viewPths}/" . "{$filename}.html";
        //exit($source);
        //$compile = $is_sys ? $realPaths . "/data/tpl/web/{$_W['template']}/{$filename}.tpl.php" : $realPaths . "/data/tpl/web/{$_W['template']}/upgrades/{$filename}.tpl.php";
        $compile = $is_sys ? $realPaths . "/runtime/admin/temp/{$filename}.tpl.php" : $realPaths . "/runtime/admin/temp/{$filename}.tpl.php";
        if ($is_sys) {
            if (!is_file($source)) {
                //$source = $realPaths . "/web/themes/default/{$filename}.html";
                $source = __DIR__ . "/View/{$viewPths}/" . "{$filename}.html";
                //$compile = $realPaths . "/data/tpl/web/default/{$filename}.tpl.php";
                $compile = $realPaths . "/runtime/admin/temp/{$filename}.tpl.php";
            }
        } else {
            if (!is_file($source)) {
                $source = __DIR__ . "/View/{$viewPths}/" . "{$filename}.html";
                //$compile = $realPaths . "/data/tpl/web/default/upgrades/{$filename}.tpl.php";
                $compile = $realPaths . "/runtime/admin/temp/{$filename}.tpl.php";
            }
        }

        if (!is_file($source)) {
            echo "template source '{$filename}' is not exist!";
            return '';
        }
        if (!is_file($compile) || filemtime($source) > filemtime($compile)) {
            weba_template_compile($source, $compile);
        }
        if (is_file($compile)) {

            switch ($flag) {
                case "TEMPLATE_DISPLAY":
                //case 0:
                default:
                    extract($GLOBALS, EXTR_SKIP);
                    return $compile;
                    break;
                case "TEMPLATE_FETCH":
                //case 1:
                    extract($GLOBALS, EXTR_SKIP);
                    ob_flush();
                    ob_clean();
                    ob_start();

                    return $compile;
                    $contents = ob_get_contents();
                    ob_clean();
                    return $contents;
                    break;
                case "TEMPLATE_INCLUDEPATH":
                //case 2:
                    return $compile;
                    break;
            }

        }
    }
}
if (!function_exists('weba_template_compile')) {
    function weba_template_compile($from, $to, $inmodule = false)
    {
        $path = dirname($to);
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $content = weba_template_parse(file_get_contents($from), $inmodule);
        file_put_contents($to, $content);
    }
}
if (!function_exists('weba_template_parse')) {
    function weba_template_parse($str, $inmodule = false)
    {
        $str = preg_replace('/<!--{(.+?)}-->/s', '{$1}', $str);
        /*$str = preg_replace('/{template\s+(.+?)}/', '<?php include _template($1, 1 ,"TEMPLATE_INCLUDEPATH");?>', $str);*/
        /*$str = preg_replace('/{template\s+(.+?)}/', '<?php include template($1, "TEMPLATE_INCLUDEPATH");?>', $str);*/
        $str = preg_replace('/{templates\s+(.+?)}/', '<?php include weba_template($1, 0 ,"TEMPLATE_INCLUDEPATH");?>', $str);
        $str = preg_replace('/{php\s+(.+?)}/', '<?php $1?>', $str);
        $str = preg_replace('/{if\s+(.+?)}/', '<?php if($1) { ?>', $str);
        $str = preg_replace('/{else}/', '<?php } else { ?>', $str);
        $str = preg_replace('/{else ?if\s+(.+?)}/', '<?php } else if($1) { ?>', $str);
        $str = preg_replace('/{\/if}/', '<?php } ?>', $str);
        $str = preg_replace('/{loop\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2) { ?>', $str);
        $str = preg_replace('/{loop\s+(\S+)\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2 => $3) { ?>', $str);
        $str = preg_replace('/{\/loop}/', '<?php } } ?>', $str);
        $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}/', '<?php echo $1;?>', $str);
        $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\]\'\"\$]*)}/', '<?php echo $1;?>', $str);
        $str = preg_replace('/{url\s+(\S+)}/', '<?php echo url($1);?>', $str);
        $str = preg_replace('/{url\s+(\S+)\s+(array\(.+?\))}/', '<?php echo url($1, $2);?>', $str);
        $str = preg_replace('/{media\s+(\S+)}/', '<?php echo tomedia($1);?>', $str);
        $str = preg_replace_callback('/<\?php([^\?]+)\?>/s', "weba_template_addquote", $str);
        $str = preg_replace('/{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}/s', '<?php echo $1;?>', $str);
        $str = str_replace('{##', '{', $str);
        $str = str_replace('##}', '}', $str);
        if (!empty($GLOBALS['_W']['setting']['remote']['type'])) {
            $str = str_replace('</body>', "<script>$(function(){\$('img').attr('onerror', '').on('error', function(){if (!\$(this).data('check-src') && (this.src.indexOf('http://') > -1 || this.src.indexOf('https://') > -1)) {this.src = this.src.indexOf('{$GLOBALS['_W']['attachurl_local']}') == -1 ? this.src.replace('{$GLOBALS['_W']['attachurl_remote']}', '{$GLOBALS['_W']['attachurl_local']}') : this.src.replace('{$GLOBALS['_W']['attachurl_local']}', '{$GLOBALS['_W']['attachurl_remote']}');\$(this).data('check-src', true);}});});</script></body>", $str);
        }
        /*$str = "<?php defined('IN_IA') or exit('Access Denied');?>" . $str;*/
        return $str;
    }
}
if (!function_exists('weba_template_addquote')) {
    function weba_template_addquote($matchs)
    {
        $code = "<?php {$matchs[1]}?>";
        $code = preg_replace('/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\](?![a-zA-Z0-9_\-\.\x7f-\xff\[\]]*[\'"])/s', "['$1']", $code);
        return str_replace('\\\"', '\"', $code);
    }
}
if (!function_exists('weba_getip')) {
    function weba_getip()
    {
        static $ip = '';
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] as $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip)) {
            return $ip;
        } else {
            return '127.0.0.1';
        }
    }
}
if (!function_exists('weba_authcode')) {
    function weba_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key = md5($key != '' ? $key : 'webaapp');
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }

    }
}
if (!function_exists('weba_error')) {
    function weba_error($errno, $message = '')
    {
        return array(
            'errno' => $errno,
            'message' => $message,
        );
    }
}
if (!function_exists('weba_is_error')) {
    function weba_is_error($data)
    {
        if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && $data['errno'] == 0)) {
            return false;
        } else {
            return true;
        }
    }
}
if (!function_exists('weba_arraysort')) {
    function weba_arraysort($array, $keys, $sort = 'asc')
    {
        $newArr = $valArr = array();
        foreach ($array as $key => $value) {
            $valArr[$key] = $value[$keys];
        }
        ($sort == 'asc') ? asort($valArr) : arsort($valArr);//先利用keys对数组排序，目的是把目标数组的key排好序
        reset($valArr); //指针指向数组第一个值
        foreach ($valArr as $key => $value) {
            $newArr[$key] = $array[$key];
        }
        return $newArr;
    }
}
