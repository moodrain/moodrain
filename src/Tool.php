<?php
namespace Muyu;
use Muyu\Support\Router;
use Muyu\Support\Seeder;
use \PDO;

class Tool
{
    public static function cors() : void
    {
        header('Access-Control-Allow-Origin: *');
    }
    public static function route() : void
    {
        $router = Tool::router();
        $router->route();
    }
    public static function router()
    {
        return new Router();
    }
    public static function uuid() : string
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    public static function ignoreCn(string $str) : string
    {
        return preg_replace('/([\x80-\xff]*)/i','',$str);
    }
    public static function hasCn(string $str) : bool
    {
        return preg_match('/([\x81-\xfe][\x40-\xfe])/', $str);
    }
    public static function validate(string $type, $value) : bool
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
    public static function timezone(string $timezone = 'PRC') : void
    {
        date_default_timezone_set($timezone);
    }
    public static function date() : string
    {
        return date('Y-m-d H:i:s');
    }
    public static function rand(array $array)
    {
        return $array[array_rand($array)];
    }
    public static function hump(string $str) : string
    {
        return preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        }, $str);
    }
    public static function pdo(array $conf = null, array $attr = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION], string $muyuConfig = 'database.default') : PDO
    {
        $config  = new Config();
        $host = $conf['host'] ?? $config( $muyuConfig . '.host');
        $type = $conf['type'] ?? $config($muyuConfig . '.type');
        $user = $conf['user'] ?? $config($muyuConfig . '.user');
        $pass = $conf['pass'] ?? base64_decode($config($muyuConfig . '.pass'));
        $db   = $conf['db']   ?? $config($muyuConfig . '.db');
        return new PDO("$type:host=$host;dbname=$db;charset=utf8", $user, $pass, $attr);
    }
    public static function log($log, string $muyuConfig = 'log.default') : void
    {
        $config = new Config();
        $file = fopen($config($muyuConfig . '.file'), 'a');
        $log = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        fwrite($file, $log . PHP_EOL);
        fclose($file);
    }
    public static function logA(string $log, string $level = 'INFO', string $muyuConfig = 'log.default') : void
    {
        $config = new Config();
        $file = fopen($config($muyuConfig . '.file'), 'a');
        $log = Tool::date() . ' ' . $level . ': ' . $log;
        fwrite($file, $log . PHP_EOL);
        fclose($file);
    }
    public static function res(int $code, string $msg, $data, int $status = null) : string
    {
        $statusHeader = 'HTTP/1.1 ';
        switch($status)
        {
            case 200 : $statusHeader .= '200 OK';break;
            case 301 : $statusHeader .= '301 Moved Permanently';break;
            case 302 : $statusHeader .= '302 Found';break;
            case 307 : $statusHeader .= '307 Temporary Redirect';break;
            case 308 : $statusHeader .= '308 Permanent Redirect';break;
            case 400 : $statusHeader .= '400 Bad Request';break;
            case 401 : $statusHeader .= '401 Unauthorized';break;
            case 403 : $statusHeader .= '403 Forbidden';break;
            case 404 : $statusHeader .= '404 Not Found';break;
            case 451 : $statusHeader .= '451 Unavailable For Legal Reasons';break;
            case 500 : $statusHeader .= '500 Internal Server Error';break;
            default  : $statusHeader .= '200 OK';
        }
        header($statusHeader);
        header('Content-Type: application/json');
        return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
    public static function abc123(string $in, bool $up = false) : string
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
    public static function deep($arr) : int
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
    public static function strBetween(string $str, string $kw1, string $kw2, bool $containKw = false) : string
    {
        $st = stripos($str, $kw1);
        $postStr = substr($str, $st);
        $ed = stripos($postStr, $kw2) + strlen($str) - strlen($postStr);
        if($st === false || $ed === false || $ed <= $st)
            return $containKw ? $kw1 . $kw2 : '';
        $rs = substr($str, $st + strlen($kw1), $ed - $st - strlen($kw1));
        return $containKw ? $kw1 . $rs . $kw2 : $rs;
    }
    public static function seeder(string $seeder = null)
    {
        return new Seeder($seeder);
    }
    public static function isSet($key, array $array) : bool
    {
        return array_key_exists($key, $array);
    }
    public static function ext(string $filename) : string
    {
        return explode('.', basename($filename))[1] ?? null;
    }
    public static function textToImg(string $text, string $filename = null, int $fontSize = 20, string $fontType = __DIR__ . '/../storage/font/simyou.ttf')
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
    public static function orderDownload(string $url, string $ext = 'jpg',int $start = 1,int $stop = 30, string $folder = 'download')
    {
        $curl = new Curl();
        self::mkdir($folder);
        for($i = $start;$i <= $stop;$i++)
            file_put_contents($folder . '/' . $i . '.' . $ext,$curl->url($url)->path($i . '.' . $ext)->get());
    }
    public static function mkdir(string $dir)
    {
        $parent = dirname($dir);
        if(!file_exists($parent))
            self::mkdir($parent);
        @mkdir($dir);
    }
    public static function rmdir(string $dir)
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
    public static function gmt(int $time = null) : string
    {
        $time = $time ?? time();
        return gmdate('D, d M Y H:i:s T', $time);
    }
    public static function gmt_iso8601(int $timestamp = null, string $timezone = null) : string
    {
        $date = new \DateTime(date(DATE_ATOM, $timestamp ?? time()), new \DateTimeZone(date_default_timezone_get()));
        if($timezone)
            $date->setTimezone(new \DateTimeZone($timezone));
        return $date->format(DATE_ATOM);
    }
}