<?php
require('vendor/autoload.php');
$curl = new Muyu\Curl();
echo $curl->url('https://moodrain.cn')->get();