<?php
namespace Muyu;
use Muyu\Support\HttpStatus;
use Muyu\Support\Router;
use Muyu\Support\Seeder;
use \PDO;

class Tool
{
    static function dd($value) : void
    {
        var_dump($value);
        exit();
    }
    static function cors() : void
    {
        header('Access-Control-Allow-Origin: *');
    }
    static function route() : void
    {
        $router = Tool::router();
        $router->route();
    }
    static function router()
    {
        return new Router();
    }
    static function uuid() : string
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    static function ignoreCn(string $str) : string
    {
        return preg_replace('/([\x80-\xff]*)/i','',$str);
    }
    static function hasCn(string $str) : bool
    {
        return preg_match('/([\x81-\xfe][\x40-\xfe])/', $str);
    }
    static function validate(string $type, $value) : bool
    {
        switch($type)
        {
            case 'phone' : return preg_match("/^1[34578]\d{9}$/", $value);
            case 'email' : return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'url'   : return filter_var($value, FILTER_VALIDATE_URL);
            case 'ip'    : return filter_var($value, FILTER_VALIDATE_IP);
            case 'int'   : return filter_var($value, FILTER_VALIDATE_INT);
            case 'float' : return filter_var($value, FILTER_VALIDATE_FLOAT);
        }
        return false;
    }
    static function timezone(string $timezone = 'PRC') : void
    {
        date_default_timezone_set($timezone);
    }
    static function date() : string
    {
        return date('Y-m-d H:i:s');
    }
    static function rand(array $array)
    {
        return $array[array_rand($array)];
    }
    static function hump(string $str) : string
    {
        return preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        }, $str);
    }
    static function pdo(array $conf = null, array $attr = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION], string $muyuConfig = 'database.default') : PDO
    {
        $config  = new Config();
        $host = $conf['host'] ?? $config( $muyuConfig . '.host');
        $type = $conf['type'] ?? $config($muyuConfig . '.type');
        $user = $conf['user'] ?? $config($muyuConfig . '.user');
        $pass = $conf['pass'] ?? base64_decode($config($muyuConfig . '.pass'));
        $db   = $conf['db']   ?? $config($muyuConfig . '.db');
        return new PDO("$type:host=$host;dbname=$db;charset=utf8", $user, $pass, $attr);
    }
    static function log($log, string $muyuConfig = 'log.default') : void
    {
        $config = new Config();
        $file = fopen($config($muyuConfig . '.file'), 'a');
        $log = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        fwrite($file, $log . PHP_EOL);
        fclose($file);
    }
    static function logA(string $log, string $level = 'INFO', string $muyuConfig = 'log.default') : void
    {
        $config = new Config();
        $file = fopen($config($muyuConfig . '.file'), 'a');
        $log = Tool::date() . ' ' . $level . ': ' . $log;
        fwrite($file, $log . PHP_EOL);
        fclose($file);
    }
    static function res(int $code, string $msg, $data, int $status = null) : string
    {
        switch($status)
        {
            case 200 : $statusHeader = HttpStatus::_200();break;
            case 301 : $statusHeader = HttpStatus::_301();break;
            case 302 : $statusHeader = HttpStatus::_302();break;
            case 307 : $statusHeader = HttpStatus::_307();break;
            case 308 : $statusHeader = HttpStatus::_308();break;
            case 400 : $statusHeader = HttpStatus::_400();break;
            case 401 : $statusHeader = HttpStatus::_401();break;
            case 403 : $statusHeader = HttpStatus::_403();break;
            case 404 : $statusHeader = HttpStatus::_404();break;
            case 451 : $statusHeader = HttpStatus::_451();break;
            case 500 : $statusHeader = HttpStatus::_500();break;
            default  : $statusHeader = HttpStatus::_200();
        }
        header($statusHeader);
        header('Content-Type: application/json');
        return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
    static function abc123(string $in, bool $up = false) : string
    {
        $ascii = ord($in);
        switch($ascii)
        {
            case ($ascii >= 65 && $ascii <= 90) : return $ascii - 64;
            case ($ascii >= 97 && $ascii <= 122) : return $ascii - 96;
            case ($in >= 1 && $in <= 26 && $up) : return chr($in + 64);
            case ($in >= 1 && $in <= 26 && !$up) : return chr($in + 96);
            default : return null;
        }
    }
    static function deep($arr) : int
    {
        $deep = 1;
        if(!is_array($arr))
            return 0;
        while(is_array(current($arr)))
        {
            $deep++;
            $arr = current($arr);
        }
        return $deep;
    }
    static function strBetween(string $str, string $kw1, string $kw2, bool $containKw = false) : string
    {
        $st = stripos($str, $kw1);
        $postStr = substr($str, $st);
        $ed = stripos($postStr, $kw2) + strlen($str) - strlen($postStr);
        if($st === false || $ed === false || $ed <= $st)
            return $containKw ? $kw1 . $kw2 : '';
        $rs = substr($str, $st + strlen($kw1), $ed - $st - strlen($kw1));
        return $containKw ? $kw1 . $rs . $kw2 : $rs;
    }
    static function seeder(string $seeder = null)
    {
        return new Seeder($seeder);
    }
    static function isSet($key, array $array) : bool
    {
        return array_key_exists($key, $array);
    }
    static function ext(string $filename) : ?string
    {
        return explode('.', basename($filename))[1] ?? null;
    }
    static function textToImg(string $text, string $filename = null, int $fontSize = 20, string $fontType = __DIR__ . '/../storage/font/simyou.ttf')
    {
        $im = imagecreatetruecolor(strlen($text) * $fontSize * (self::hasCn($text) ? 5/11 : 2/3), (substr_count($text, "\n")+1) * $fontSize * 31/22);
        imagesavealpha($im, true);
        $color = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $color);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagettftext($im, $fontSize, 0, 0, $fontSize, $black, $fontType, $text);
        if($filename && !file_exists(dirname($filename)))
            self::mkdir(dirname($filename));
        imagepng($im, $filename);
        imagedestroy($im);
    }
    static function mkdir(string $dir)
    {
        $parent = dirname($dir);
        if(!file_exists($parent))
            self::mkdir($parent);
        @mkdir($dir);
    }
    static function rmdir(string $dir)
    {
        $files = scandir($dir);
        $files = array_slice($files, 2);
        foreach($files as $file)
        {
            $file = $dir . '/' . $file;
            is_dir($file) ? self::rmdir($file) : @unlink($file);
        }
        @rmdir($dir);
    }
    static function gmt(int $time = null) : string
    {
        $time = $time ?? time();
        return gmdate('D, d M Y H:i:s T', $time);
    }
    static function gmt_iso8601(int $timestamp = null, string $timezone = null) : string
    {
        $date = new \DateTime(date(DATE_ATOM, $timestamp ?? time()), new \DateTimeZone(date_default_timezone_get()));
        if($timezone)
            $date->setTimezone(new \DateTimeZone($timezone));
        return $date->format(DATE_ATOM);
    }
}