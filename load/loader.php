<?php

$dir = __DIR__ . '/../';

$require = function($path) use ($dir) {
    require $dir . $path;
};

$require('vendor/autoload.php');