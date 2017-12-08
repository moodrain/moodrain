<?php
namespace Muyu;
use \PDO;

class Tool
{
    private static $config;
    public static function pdo($db = null, $errmode = PDO::ERRMODE_EXCEPTION , $type = null, $host = null, $user = null, $pass = null)
    {
        if(!Self::$config)
            Self::$config = new Config();
        $config = Self::$config;
        $type = $type ? $type : $config('db_type');
        $host = $host ? $host : $config('db_host');
        $user = $user ? $user : $config('db_user');
        $pass = $pass ? $pass : $config('db_pass');
        $db = $db ? $db : $config('db_name');
        return new PDO("$type:host=$host;dbname=$db;charset=utf8", $user, $pass, [PDO::ATTR_ERRMODE => $errmode]);
    }
    public static function strBetween($kw, $mark1, $mark2)
    {
        $st = stripos($kw, $mark1);
        $ed = stripos($kw, $mark2);
        if(!$st || !$ed || $$ed <= $st)
            return '';
        $kw = substr($kw, $st + strlen($mark1), $ed - $st - strlen($mark1));
        return $kw;
    }
}
