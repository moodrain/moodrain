<?php
namespace Muyu\Support\Fun;
use Muyu\Config;

function conf(...$e) {
    static $conf = null;
    $conf = $conf ? $conf : new Config;
    if(count($e) == 1) {
        return $conf($e[0]);
    } else {
        return $conf($e[0], $e[1]);
    }
}