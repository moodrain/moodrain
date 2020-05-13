<?php
namespace Muyu\Support\Tool;

trait ToolDecoupleTrait {

    static public function httpMethod() {
        return $_SERVER['REQUEST_METHOD'] ?? null;
    }

    static public function cors() {
        header('Access-Control-Allow-Origin: ' . self::getallheaders()['Origin']);
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Credentials: true');
    }

    static public function toDownload($filename) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }

    static public function toImage($ext) {

    }

    static public function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    static public function dirFilter($dirname) {
        $filter = ['[', '\\', '/', ':', '*', '?', '"', '<', '>', '|', ']', "'"];
        return self::name(str_replace($filter, '', $dirname));
    }

    static public function strReplaceOnce($find, $replace, $string) {
        $pos = strpos($string, $find);
        return $pos !== false ? substr_replace($string, $replace, $pos, strlen($find)) : $string;
    }

    static public function validate($type, $value) {
        switch($type) {
            case 'phone' : return preg_match("/^1[34578]\d{9}$/", $value);
            case 'email' : return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'url'   : return filter_var($value, FILTER_VALIDATE_URL);
            case 'ip'    : return filter_var($value, FILTER_VALIDATE_IP);
            case 'int'   : return filter_var($value, FILTER_VALIDATE_INT);
            case 'float' : return filter_var($value, FILTER_VALIDATE_FLOAT);
        }
        return false;
    }

    static public function timezone($timezone = 'PRC') {
        date_default_timezone_set($timezone);
    }

    static public function defineTime() {
        define('DATE', date('Y-m-d'));
        define('TIME', date('H:i:s'));
        define('DATETIME', date('Y-m-d H:i:s'));
        define('UNIX_TIME', time());
    }

    static public function rand($array) {
        return $array[array_rand($array)];
    }

    static public function hump($str) {
        return preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        }, $str);
    }

    static public function abc123($in, $up = false) {
        $ascii = ord($in);
        switch($ascii) {
            case ($ascii >= 65 && $ascii <= 90) : return $ascii - 64;
            case ($ascii >= 97 && $ascii <= 122) : return $ascii - 96;
            case ($in >= 1 && $in <= 26 && $up) : return chr($in + 64);
            case ($in >= 1 && $in <= 26 && !$up) : return chr($in + 96);
            default : return null;
        }
    }

    static public function deep($arr) {
        $deep = 1;
        if(!is_array($arr)) {
            return 0;
        }
        while(is_array(current($arr))) {
            $deep++;
            $arr = current($arr);
        }
        return $deep;
    }

    static public function strBetween($str, $kw1, $kw2, $containKw = false) {
        $st = stripos($str, $kw1);
        $postStr = substr($str, $st);
        $ed = stripos($postStr, $kw2) + strlen($str) - strlen($postStr);
        if($st === false || $ed === false || $ed <= $st) {
            return $containKw ? $kw1 . $kw2 : '';
        }
        $rs = substr($str, $st + strlen($kw1), $ed - $st - strlen($kw1));
        return $containKw ? $kw1 . $rs . $kw2 : $rs;
    }

    static public function ext($filename) {
        $info = explode('.', basename($filename));
        return $info[count($info)-1] ?? null;
    }

    static public function name($filename) {
        $index = strrpos($filename, '.');
        if($index === false) {
            return $filename;
        }
        return substr($filename, 0, $index);
    }

    static public function textToImg($text, $filename = null, $fontSize = 20, $fontColor = [0, 0, 0], $fontType = __DIR__ . '/../../../storage/font/simyou.ttf') {
        $hasCn = preg_match('/([\x81-\xfe][\x40-\xfe])/', $text);
        $im = imagecreatetruecolor(strlen($text) * $fontSize * ($hasCn ? 5/11 : 2/3), (substr_count($text, "\n")+1) * $fontSize * 31/22);
        imagesavealpha($im, true);
        $color = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $color);
        $fontColor = imagecolorallocate($im, $fontColor[0], $fontColor[1], $fontColor[2]);
        imagettftext($im, $fontSize, 0, 0, $fontSize, $fontColor, $fontType, $text);
        if($filename && !file_exists(dirname($filename))) {
            self::mkdir(dirname($filename));
        }
        imagepng($im, $filename);
        imagedestroy($im);
    }

    static public function mkdir($dir) {
        $parent = dirname($dir);
        if(!file_exists($parent)) {
            self::mkdir($parent);
        }
        return @mkdir($dir);
    }

    static public function rmdir($dir) {
        $files = scandir($dir);
        $files = array_slice($files, 2);
        foreach($files as $file) {
            $file = $dir . '/' . $file;
            is_dir($file) ? self::rmdir($file) : @unlink($file);
        }
        @rmdir($dir);
    }

    static public function scandir($dir = '.') {
        substr($dir, -1) == '/' && $dir = substr($dir, 0 , -1);
        $return = [];
        $files = scandir($dir);
        array_shift($files);
        array_shift($files);
        foreach($files as $file) {
            $file = $dir . '/' . $file;
            if(is_dir($file)) {
                $return = array_merge($return, self::scandir($file));
            } else {
                $return[] = $file;
            }
        }
        return $return;
    }


    static public function gmt($time = null) {
        $time = $time ?? time();
        return gmdate('D, d M Y H:i:s T', $time);
    }

    static public function gmt_iso8601($timestamp = null, $timezone = null) {
        $date = new \DateTime(date(DATE_ATOM, $timestamp ?? time()), new \DateTimeZone(date_default_timezone_get()));
        $timezone && $date->setTimezone(new \DateTimeZone($timezone));;
        return $date->format(DATE_ATOM);
    }

    static public function getallheaders() {
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                $headers = array ();
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_')
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
                return $headers;
            }
        }
        return getallheaders();
    }

    static public function setType(& $arr, $key, $type) {
        $nextKey = function (& $str) {
            $i = strpos($str, '.');
            if($i === false) {
                $tmp = $str;
                $str = '';
                return $tmp;
            }
            $key = substr($str, 0, $i);
            $str = substr($str, $i + 1);
            return $key;
        };
        $val = & $arr;
        $k = $nextKey($key);
        if($k == '*') {
            foreach($val as & $v) {
                self::setType($v, $key, $type);
            }
            return;
        }
        if($key === '') {
            if(isset($val[$k]) && ! is_array($val[$k]) && ! is_object($val[$k])) {
                switch($type) {
                    case 'int':
                    case 'integer': $val[$k] = (int) $val[$k];break;
                    case 'str':
                    case 'string': $val[$k] = (string) $val[$k];break;
                    case 'bool':
                    case 'boolean': $val[$k] = $val[$k] == 'true' ? true : false;break;
                }
            }
        } else {
            self::setType($arr[$k], $key, $type);
        }
    }

}