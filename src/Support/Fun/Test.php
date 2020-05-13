<?php

namespace Muyu\Support\Fun;

use Muyu\Curl;

function curl($url) {
    $curl = new Curl($url);
    $curl->get();
    var_dump($curl->responseHeader());
    var_dump( $curl->content());
}

function dd(...$args) {
    $toDump = count($args) === 1 ? $args[0] : $args;
    var_dump($toDump);
    exit;
}

function dj(...$args) {
    $toJson = count($args) === 1 ? $args[0] : $args;
    header('Content-Type: application/json');
    echo json_encode($toJson, 128|256);
    exit;
}