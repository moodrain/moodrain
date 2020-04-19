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

function de(...$elem) {
    dd($elem);
    exit;
}