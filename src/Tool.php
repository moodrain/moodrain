<?php
namespace Muyu;
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
        return new class
        {
            public function route(string $url = null)
            {
                $url = $url ?? $_SERVER['REQUEST_URI'];
                $request = explode('/', $url);
                array_shift($request);
                echo $this->handle($request);
            }
            private function handle(array $url)
            {
                if(count($url) == 1)
                    array_unshift($url, 'Index');
                $controller = ucfirst($url[0]);
                $action = Tool::hump(explode('?', $url[1])[0]);
                $action = $action == '' ? 'index' : $action;
                define('Controller', $controller);
                define('Action', $action);
                $class = 'App\Controller\\' . $controller;
                if(!class_exists($class))
                    return Tool::res(404,'页面不存在', null, 404);
                $obj = new $class;
                if(!method_exists ($obj, $action))
                    return Tool::res(404,'页面不存在', null, 404);
                return call_user_func([$obj, $action]);
            }
        };
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
    public static function ignoreCn(String $str) : string
    {
        return preg_replace('/([\x80-\xff]*)/i','',$str);
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
    public static function log($log, $muyuConfig = 'log') : void
    {
        $config = new Config();
        $file = fopen($config($muyuConfig . '.file'), 'a');
        $log = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
    public static function strBetween(string $str, string $kw1, string $kw2) : string
    {
        $st = stripos($str, $kw1);
        $ed = stripos($str, $kw2);
        if(!$st || !$ed || $ed <= $st)
            return '';
        $str = substr($str, $st + strlen($kw1), $ed - $st - strlen($kw1));
        return $str;
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
    public static function gmt(int $time = null) : string
    {
        $time = $time ?? time();
        return gmdate('D, d M Y H:i:s T', $time);
    }
    public static function gmt_iso8601(int $time = null) : string
    {
        $time = $time ?? time();
        $dtStr = date("c", $time);
        $myDatetime = new \DateTime($dtStr);
        $expiration = $myDatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
}