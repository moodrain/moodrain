<?php
require('vendor/autoload.php');
$curl = new Muyu\Curl('https://moodrain.cn/api/method');
echo $curl->get();