<?php
namespace Muyu;
class Tool
{
    public static function getPDO($host,$db,$user,$pass)
    {
        return new PDO("mysql:host=$host;dbname=$db;charset=utf8",$user,$pass,[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    public static function getStrBetween($kw, $mark1, $mark2)
    {
        $st = stripos($kw, $mark1);
        $ed = stripos($kw, $mark2);
        if(!$st || !$ed || $$ed <= $st)
            return '';
        $kw = substr($kw, $st + strlen($mark1), $ed - $st - strlen($mark1));
        return $kw;
    }
}
