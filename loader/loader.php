<?php

$dir = __DIR__ . '/../';

$helpers = [
    'src/Support/Helpers/helper.php',
];

$require = function($path) use ($dir) {
    if(! file_exists($dir . $path)) {
        throw new \Exception('class not found: ' . $className);
    }
    require $dir . $path;
};

foreach($helpers as $helper) {
    $require($helper);
}

spl_autoload_register(function($className) use ($require) {
    $prefix = 'Muyu';
    $nameSpaces = explode('\\', $className);
    if(array_shift($nameSpaces) != $prefix) {
        return;
    }
    $baseName = array_pop($nameSpaces);
    $path = 'src/' . (empty($nameSpaces) ? '' : implode('/', $nameSpaces) . '/') . $baseName . '.php';
    $require($path);
});