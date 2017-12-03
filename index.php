<?php
require('vendor/autoload.php');
$client = new Predis\Client();
$client->set('foo', 'bar');
$value = $client->get('foo');
echo $value;
$curl = new Muyu\Curl();
echo $curl->do();
echo 'new';
