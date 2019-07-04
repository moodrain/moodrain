<?php
namespace Muyu\Support\Fun;

use Muyu\Curl;
use Muyu\Support\Tool;

function curl($url) {
    $curl = new Curl($url);
    $curl->get();
    var_dump( $curl->content());
    var_dump($curl->responseHeader());
}

function dd(...$elem) {
    Tool::dd($elem);
}

function ddd(...$elem) {
    var_dump($elem);
}

function de(...$objs) {
    foreach($objs as $obj) {
        var_dump($obj->error()->dd());
    }
    exit;
}