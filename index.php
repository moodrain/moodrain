<?php
require('vendor/autoload.php');
$config = new Muyu\Config();
$config->tryInit(['b' => 2]);
$config->dump();